<?php

namespace App\Services;

use App\Models\AiRun;
use App\Models\Book;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TitlePageEnrichmentService
{
    private const PROMPT_VERSION = 'title-page-enrichment-v1';

    private const ENRICHED_FIELDS = [
        'author',
        'title',
        'place',
        'publisher',
        'publication_date',
    ];

    public function enrich(Book $book): AiRun
    {
        $book->loadMissing(['fields', 'sources']);

        $provider = config('services.antiqscan_enrichment.provider', 'mammouth');
        $model = config('services.antiqscan_enrichment.model', 'sonar');
        $baseUrl = rtrim((string) config('services.antiqscan_enrichment.base_url'), '/');
        $apiKey = config('services.antiqscan_enrichment.api_key');
        $maxOutputTokens = (int) config('services.antiqscan_enrichment.max_output_tokens', 1200);
        $systemPrompt = $this->systemPrompt();
        $visionJson = $this->visionJson($book);

        $run = AiRun::create([
            'book_id' => $book->id,
            'run_type' => 'title_page_enrichment',
            'provider' => $provider,
            'model' => $model,
            'status' => 'running',
            'prompt' => [
                'version' => self::PROMPT_VERSION,
                'system' => $systemPrompt,
                'input' => $visionJson,
            ],
            'started_at' => now(),
        ]);

        try {
            if (blank($apiKey)) {
                throw new RuntimeException('Configuration enrichissement IA absente : clé API manquante.');
            }

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->withUserAgent('Mozilla/5.0 AntiQScan/1.0')
                ->timeout(90)
                ->post($baseUrl.'/chat/completions', [
                    'model' => $model,
                    'max_tokens' => $maxOutputTokens,
                    'temperature' => 0.2,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode($visionJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                        ],
                    ],
                ]);

            if ($response->failed()) {
                throw new RuntimeException('Erreur API enrichissement HTTP '.$response->status().' : '.str($response->body())->limit(1000));
            }

            $responseJson = $response->json();
            $parsed = $this->decodeStrictJson((string) data_get($responseJson, 'choices.0.message.content'));

            $this->applyEnrichment($book, $parsed);

            $run->update([
                'input_tokens' => data_get($responseJson, 'usage.prompt_tokens'),
                'output_tokens' => data_get($responseJson, 'usage.completion_tokens'),
                'status' => 'succeeded',
                'response' => [
                    'raw' => $responseJson,
                    'parsed' => $parsed,
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

    private function systemPrompt(): string
    {
        return implode("\n", [
            'Tu es un assistant bibliographique spécialisé en livres anciens.',
            'Tu reçois un JSON issu d’une première lecture vision de la page de titre.',
            'Tu dois enrichir et normaliser ces informations en recherchant si nécessaire sur internet.',
            'Réponds uniquement en JSON strict, sans markdown, sans commentaire.',
            'Schéma exact : {"display_title":null,"author":null,"title":null,"publisher":null,"place":null,"publication_date":null,"academic_notice":null,"sources":[{"title":null,"url":null,"citation":null}]}',
            'display_title : format "NOM, Titre raccourci, date," et 80 caractères maximum.',
            'author : format "NOM, Prénom" ; nom entièrement en majuscules, prénom en minuscules sauf première lettre en majuscule. Si le prénom est absent du JSON vision, recherche le nom complet de l’auteur sur internet.',
            'title : transcription exacte et complète du titre indiqué par le JSON vision, en minuscule sauf première lettre en majuscule.',
            'publisher : nom complet indiqué, en minuscule sauf première lettre des noms propres en majuscule.',
            'place : ville d’édition mentionnée.',
            'publication_date : chiffres arabes. Si la date est déduite par recherche et non visible dans le JSON vision, mets-la entre crochets [].',
            'academic_notice : description factuelle du sujet et de la portée de l’ouvrage, sourcée par des références fiables.',
            'sources : références fiables utilisées, par priorité bibliothèques nationales, universités, catalogues collectifs, encyclopédies reconnues.',
            'Ne modifie pas les faits visibles sans raison sourcée.',
            'Si une donnée reste inconnue malgré recherche, mets null.',
        ]);
    }

    private function visionJson(Book $book): array
    {
        $fields = $book->fields->keyBy('field_key');

        return [
            'author' => $fields->get('author')->value ?? null,
            'title' => $fields->get('title')->value ?? null,
            'subtitle' => $fields->get('subtitle')->value ?? null,
            'place' => $fields->get('place')->value ?? null,
            'publisher' => $fields->get('publisher')->value ?? null,
            'publisher_address' => $fields->get('publisher_address')->value ?? null,
            'publication_date' => $fields->get('publication_date')->value ?? null,
            'illustration_statement' => $fields->get('illustration_statement')->value ?? null,
            'edition_statement' => $fields->get('edition_statement')->value ?? null,
            'visible_notes' => $fields->get('visible_notes')->value ?? null,
        ];
    }

    private function decodeStrictJson(string $content): array
    {
        $json = trim($content);
        if (str_starts_with($json, '```')) {
            $json = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $json) ?? $json;
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Réponse enrichissement non JSON ou invalide.');
        }

        return $decoded;
    }

    private function applyEnrichment(Book $book, array $parsed): void
    {
        foreach (self::ENRICHED_FIELDS as $fieldKey) {
            $value = data_get($parsed, $fieldKey);
            if (filled($value)) {
                $book->fields()->where('field_key', $fieldKey)->update([
                    'value' => trim((string) $value),
                    'origin' => 'ai_enriched',
                    'confidence' => null,
                    'is_validated' => false,
                ]);
            }
        }

        $book->update([
            'working_title' => filled(data_get($parsed, 'display_title')) ? trim((string) data_get($parsed, 'display_title')) : $book->working_title,
            'catalogue_note' => filled(data_get($parsed, 'academic_notice')) ? trim((string) data_get($parsed, 'academic_notice')) : $book->catalogue_note,
        ]);

        foreach ((array) data_get($parsed, 'sources', []) as $source) {
            if (! filled(data_get($source, 'url')) && ! filled(data_get($source, 'title'))) {
                continue;
            }

            $book->sources()->create([
                'source_type' => 'ai_enrichment',
                'title' => data_get($source, 'title'),
                'url' => data_get($source, 'url'),
                'citation' => data_get($source, 'citation'),
                'notes' => 'Source proposée par enrichissement IA, à valider.',
                'user_approved' => false,
            ]);
        }
    }
}
