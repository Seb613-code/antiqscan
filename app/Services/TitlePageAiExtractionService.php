<?php

namespace App\Services;

use App\Models\AiRun;
use App\Models\Book;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TitlePageAiExtractionService
{
    private const PROMPT_VERSION = 'visible-title-page-v1';

    private const ALLOWED_FIELDS = [
        'author',
        'title',
        'subtitle',
        'place',
        'publisher',
        'publisher_address',
        'publication_date',
        'illustration_statement',
        'edition_statement',
        'visible_notes',
    ];

    public function __construct(private readonly ImageIntakeService $images) {}

    public function extract(Book $book): AiRun
    {
        $book->loadMissing(['images', 'fields']);

        $provider = config('services.antiqscan_ai.provider', 'mammouth');
        $model = config('services.antiqscan_ai.model', 'gemini-2.5-flash-lite');
        $baseUrl = rtrim((string) config('services.antiqscan_ai.base_url'), '/');
        $apiKey = config('services.antiqscan_ai.api_key');
        $maxOutputTokens = (int) config('services.antiqscan_ai.max_output_tokens', 450);
        $prompt = $this->prompt();

        $run = AiRun::create([
            'book_id' => $book->id,
            'run_type' => 'title_page_extraction',
            'provider' => $provider,
            'model' => $model,
            'status' => 'running',
            'prompt' => [
                'version' => self::PROMPT_VERSION,
                'text' => $prompt,
                'allowed_fields' => self::ALLOWED_FIELDS,
            ],
            'started_at' => now(),
        ]);

        try {
            if (blank($apiKey)) {
                throw new RuntimeException('Configuration IA absente : clé API manquante.');
            }

            $image = $book->images->firstWhere('role', 'title_page') ?? $book->images->first();
            if (! $image) {
                throw new RuntimeException('Aucune image de page de titre trouvée.');
            }

            if (blank($image->optimized_path)) {
                $optimizedPath = $this->images->optimizeStoredImage($image->original_path);
                if ($optimizedPath) {
                    $image->update(['optimized_path' => $optimizedPath]);
                    $image->optimized_path = $optimizedPath;
                }
            }

            $imagePath = $image->optimized_path ?: $image->original_path;
            $imageHash = hash_file('sha256', Storage::disk('local')->path($imagePath));
            $cacheKey = hash('sha256', implode('|', [$provider, $model, self::PROMPT_VERSION, $imageHash]));

            $run->update([
                'image_hash' => $imageHash,
                'cache_key' => $cacheKey,
            ]);

            $cachedRun = AiRun::query()
                ->where('run_type', 'title_page_extraction')
                ->where('status', 'succeeded')
                ->where('cache_key', $cacheKey)
                ->whereKeyNot($run->id)
                ->latest()
                ->first();

            if ($cachedRun) {
                $parsed = data_get($cachedRun->response, 'parsed', []);
                $fields = $this->allowedFields(data_get($parsed, 'fields', []));
                $this->applyFields($book, $fields);
                $book->update(['status' => 'extracted']);

                $run->update([
                    'cached_from_ai_run_id' => $cachedRun->id,
                    'input_tokens' => 0,
                    'output_tokens' => 0,
                    'estimated_cost' => 0,
                    'status' => 'cached',
                    'response' => [
                        'cached_from_ai_run_id' => $cachedRun->id,
                        'parsed' => $parsed,
                        'accepted_fields' => array_keys($fields),
                    ],
                    'finished_at' => now(),
                ]);

                return $run->fresh();
            }

            $payload = [
                'model' => $model,
                'max_tokens' => $maxOutputTokens,
                'temperature' => 0,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $prompt,
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $this->imageAsDataUrl($imagePath),
                            ],
                        ],
                    ],
                ]],
            ];

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->withUserAgent('Mozilla/5.0 AntiQScan/1.0')
                ->timeout(60)
                ->post($baseUrl.'/chat/completions', $payload);

            if ($response->failed()) {
                throw new RuntimeException('Erreur API IA HTTP '.$response->status().' : '.str($response->body())->limit(1000));
            }

            $response = $response->json();

            $content = data_get($response, 'choices.0.message.content');
            $parsed = $this->decodeStrictJson((string) $content);
            $fields = $this->allowedFields(data_get($parsed, 'fields', []));

            $this->applyFields($book, $fields);

            $book->update(['status' => 'extracted']);

            $run->update([
                'input_tokens' => data_get($response, 'usage.prompt_tokens'),
                'output_tokens' => data_get($response, 'usage.completion_tokens'),
                'status' => 'succeeded',
                'response' => [
                    'raw' => $response,
                    'parsed' => $parsed,
                    'accepted_fields' => array_keys($fields),
                ],
                'finished_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'validation_errors' => ['message' => $exception->getMessage()],
                'finished_at' => now(),
            ]);
        }

        return $run->fresh();
    }

    private function prompt(): string
    {
        return implode("\n", [
            'Tu extrais une page de titre de livre ancien pour AntiQScan.',
            'Réponds uniquement en JSON strict, sans markdown, sans commentaire.',
            'Schéma exact : {"fields":{"author":null,"title":null,"subtitle":null,"place":null,"publisher":null,"publisher_address":null,"publication_date":null,"illustration_statement":null,"edition_statement":null,"visible_notes":null}}',
            'Règles absolues : extrais seulement ce qui est visible/lisible sur l’image.',
            'Ne déduis pas, n’enrichis pas, ne complète pas depuis ta culture générale.',
            'Si un champ n’est pas visible, mets null.',
            'Garde une formulation sobre et factuelle.',
        ]);
    }

    private function imageAsDataUrl(string $path): string
    {
        $bytes = Storage::disk('local')->get($path);
        $mime = Storage::disk('local')->mimeType($path) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }

    private function decodeStrictJson(string $content): array
    {
        $json = trim($content);
        if (str_starts_with($json, '```')) {
            $json = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $json) ?? $json;
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Réponse IA non JSON ou invalide.');
        }

        return $decoded;
    }

    private function allowedFields(array $fields): array
    {
        $accepted = [];
        foreach (self::ALLOWED_FIELDS as $key) {
            $value = $fields[$key] ?? null;
            if (filled($value)) {
                $accepted[$key] = is_scalar($value) ? trim((string) $value) : null;
            }
        }

        return array_filter($accepted, fn ($value) => filled($value));
    }

    private function applyFields(Book $book, array $fields): void
    {
        $book->fields()
            ->whereIn('field_key', self::ALLOWED_FIELDS)
            ->update([
                'value' => null,
                'origin' => 'ai_visible',
                'confidence' => null,
                'is_validated' => false,
            ]);

        foreach ($fields as $key => $value) {
            $book->fields()->where('field_key', $key)->update([
                'value' => $value,
                'origin' => 'ai_visible',
                'confidence' => null,
                'is_validated' => false,
            ]);
        }
    }
}
