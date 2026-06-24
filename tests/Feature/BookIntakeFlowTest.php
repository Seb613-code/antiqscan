<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookIntakeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_lists_empty_books_and_upload_form(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('AntiQScan')
            ->assertSee("Création d'une nouvelle fiche", false)
            ->assertSee('Votre bibliothèque');
    }

    public function test_generated_urls_use_configured_public_app_url(): void
    {
        $this->get('/', ['HTTP_HOST' => '127.0.0.1:8090'])
            ->assertOk()
            ->assertSee('https://antiqscan.vatinel.fr/books', false)
            ->assertDontSee('http://127.0.0.1:8090/books', false);
    }

    public function test_user_can_create_book_from_one_title_page_image(): void
    {
        Storage::fake('local');

        $response = $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 1200, 1600),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('books', ['status' => 'uploaded']);
        $this->assertDatabaseHas('book_images', ['role' => 'title_page', 'sort_order' => 1]);

        $image = \App\Models\BookImage::query()->firstOrFail();
        $this->assertNotNull($image->optimized_path);
        Storage::disk('local')->assertExists($image->optimized_path);
        [$width, $height] = getimagesize(Storage::disk('local')->path($image->optimized_path));
        $this->assertSame(1400, max($width, $height));
    }

    public function test_created_book_receives_editable_catalogue_fields(): void
    {
        Storage::fake('local');

        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 1200, 1600),
        ]);

        foreach (['author', 'title', 'subtitle', 'place', 'publisher', 'publisher_address', 'publication_date', 'illustration_statement', 'edition_statement', 'visible_notes'] as $fieldKey) {
            $this->assertDatabaseHas('book_fields', [
                'field_key' => $fieldKey,
                'origin' => 'ai_visible',
                'is_editable' => true,
                'is_validated' => false,
            ]);
        }

        foreach (['format', 'dimensions', 'pagination', 'binding', 'condition', 'copy_notes'] as $fieldKey) {
            $this->assertDatabaseHas('book_fields', [
                'field_key' => $fieldKey,
                'origin' => 'user_manual',
                'is_editable' => true,
                'is_validated' => false,
            ]);
        }
    }

    public function test_catalogue_sheet_does_not_show_unvalidated_ai_fields(): void
    {
        $book = \App\Models\Book::factory()->create(['status' => 'extracted']);
        $book->fields()->create([
            'field_key' => 'title',
            'label' => 'Titre',
            'value' => 'Visible but not validated',
            'origin' => 'ai_visible',
            'confidence' => 0.91,
            'is_validated' => false,
            'is_editable' => true,
        ]);

        $this->get("/books/{$book->id}/catalogue")
            ->assertOk()
            ->assertDontSee('Visible but not validated');
    }

    public function test_validated_catalogue_can_be_exported_as_markdown(): void
    {
        $book = $this->bookWithOneValidatedAndOneUnvalidatedField();

        $this->get("/books/{$book->id}/export/markdown")
            ->assertOk()
            ->assertHeader('content-type', 'text/markdown; charset=UTF-8')
            ->assertSee('**Titre** — La chaleur solaire', false)
            ->assertDontSee('999 € non validé');
    }

    public function test_validated_catalogue_can_be_exported_as_json(): void
    {
        $book = $this->bookWithOneValidatedAndOneUnvalidatedField();

        $this->get("/books/{$book->id}/export/json")
            ->assertOk()
            ->assertJsonPath('book_id', $book->id)
            ->assertJsonPath('fields.0.key', 'title')
            ->assertJsonPath('fields.0.label', 'Titre')
            ->assertJsonPath('fields.0.value', 'La chaleur solaire')
            ->assertJsonMissing(['value' => '999 € non validé']);
    }

    public function test_validated_catalogue_can_be_exported_as_csv(): void
    {
        $book = $this->bookWithOneValidatedAndOneUnvalidatedField();

        $this->get("/books/{$book->id}/export/csv")
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertSee('key,label,value,origin', false)
            ->assertSee('title,Titre,"La chaleur solaire",user_validated', false)
            ->assertDontSee('999 € non validé');
    }

    private function bookWithOneValidatedAndOneUnvalidatedField(): \App\Models\Book
    {
        $book = \App\Models\Book::factory()->create(['status' => 'validated']);
        $book->fields()->create([
            'field_key' => 'title',
            'label' => 'Titre',
            'value' => 'La chaleur solaire',
            'origin' => 'user_validated',
            'is_validated' => true,
            'is_editable' => true,
        ]);
        $book->fields()->create([
            'field_key' => 'price',
            'label' => 'Prix',
            'value' => '999 € non validé',
            'origin' => 'estimate',
            'is_validated' => false,
            'is_editable' => true,
        ]);

        return $book;
    }

    public function test_book_show_page_groups_fields_by_simple_sections(): void
    {
        Storage::fake('local');

        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 1200, 1600),
        ]);

        $book = \App\Models\Book::query()->firstOrFail();

        $this->get("/books/{$book->id}")
            ->assertOk()
            ->assertSee('Champs visibles sur page de titre')
            ->assertSee('Champs physiques manuels')
            ->assertSee('Sources')
            ->assertSee('Prix / estimation')
            ->assertSee('Étape 2 — lancer l’extraction')
            ->assertSee('Lancer l’extraction IA réelle')
            ->assertSee('Tester avec le mock Mouchot')
            ->assertSee('Valider les champs remplis')
            ->assertSee('1. Image importée')
            ->assertSee('2. Extraction visible')
            ->assertSee('3. Validation humaine')
            ->assertSee('La fiche finale affiche seulement les champs validés')
            ->assertSee('Auteur')
            ->assertSee('Format');
    }

    public function test_validate_filled_action_validates_only_non_empty_fields(): void
    {
        Storage::fake('local');

        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 1200, 1600),
        ]);

        $book = \App\Models\Book::query()->firstOrFail();
        $title = $book->fields()->where('field_key', 'title')->firstOrFail();
        $subtitle = $book->fields()->where('field_key', 'subtitle')->firstOrFail();

        $this->put("/books/{$book->id}/fields", [
            'action' => 'validate_filled',
            'fields' => [
                $title->id => [
                    'label' => 'Titre',
                    'value' => 'La chaleur solaire',
                ],
                $subtitle->id => [
                    'label' => 'Sous-titre',
                    'value' => '',
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('book_fields', [
            'id' => $title->id,
            'value' => 'La chaleur solaire',
            'is_validated' => true,
        ]);
        $this->assertDatabaseHas('book_fields', [
            'id' => $subtitle->id,
            'value' => null,
            'is_validated' => false,
        ]);
    }

    public function test_home_page_shows_library_actions_for_existing_books(): void
    {
        Storage::fake('local');

        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 1200, 1600),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Modifier la fiche')
            ->assertSee('Supprimer la fiche')
            ->assertSee('width="50"', false)
            ->assertDontSee('Lancer l’extraction');
    }

    public function test_books_url_shows_library_actions_for_existing_books(): void
    {
        Storage::fake('local');

        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 1200, 1600),
        ]);

        $this->get('/books/')
            ->assertOk()
            ->assertSee('Modifier la fiche')
            ->assertSee('Supprimer la fiche')
            ->assertDontSee('Lancer l’extraction');
    }

    public function test_mock_extraction_fills_mouchot_visible_fields_without_validation(): void
    {
        Storage::fake('local');

        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 1200, 1600),
        ]);

        $book = \App\Models\Book::query()->firstOrFail();

        $this->post("/books/{$book->id}/extract/mock")
            ->assertRedirect("/books/{$book->id}");

        $this->assertDatabaseHas('book_fields', [
            'book_id' => $book->id,
            'field_key' => 'author',
            'value' => 'A. Mouchot',
            'origin' => 'ai_visible',
            'is_validated' => false,
        ]);
        $this->assertDatabaseHas('book_fields', [
            'book_id' => $book->id,
            'field_key' => 'title',
            'value' => 'La chaleur solaire et ses applications industrielles',
            'origin' => 'ai_visible',
            'is_validated' => false,
        ]);
        $this->assertDatabaseHas('ai_runs', [
            'book_id' => $book->id,
            'run_type' => 'title_page_extraction',
            'provider' => 'mock',
            'model' => 'mouchot-fixture',
            'status' => 'succeeded',
        ]);
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'status' => 'extracted',
        ]);
        $this->assertDatabaseMissing('book_fields', [
            'book_id' => $book->id,
            'field_key' => 'pagination',
            'value' => 'in-8',
        ]);
    }

    public function test_real_ai_extraction_uses_strict_json_and_only_updates_allowed_visible_fields(): void
    {
        config([
            'services.antiqscan_ai.base_url' => 'https://api.mammouth.ai/v1',
            'services.antiqscan_ai.api_key' => 'test-key',
            'services.antiqscan_ai.model' => 'gemini-2.5-flash-lite',
            'services.antiqscan_ai.max_output_tokens' => 450,
        ]);
        Http::fake([
            'api.mammouth.ai/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'fields' => [
                                'author' => 'A. Mouchot',
                                'title' => 'La chaleur solaire et ses applications industrielles',
                                'publication_date' => '1869',
                                'pagination' => 'vii-238',
                                'price' => '999 €',
                            ],
                        ]),
                    ],
                ]],
                'usage' => ['prompt_tokens' => 1200, 'completion_tokens' => 120],
            ], 200),
        ]);
        Storage::fake('local');

        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 1200, 1600),
        ]);

        $book = \App\Models\Book::query()->firstOrFail();
        $book->images()->update(['optimized_path' => null]);

        $book->fields()->where('field_key', 'publisher_address')->update([
            'value' => 'ancienne adresse à effacer',
            'origin' => 'user_validated',
            'is_validated' => true,
        ]);

        $this->post("/books/{$book->id}/extract/ai")
            ->assertRedirect("/books/{$book->id}");

        Http::assertSent(fn ($request) =>
            $request->url() === 'https://api.mammouth.ai/v1/chat/completions'
            && $request['model'] === 'gemini-2.5-flash-lite'
            && $request['max_tokens'] === 450
            && $request['messages'][0]['content'][0]['type'] === 'text'
            && str_contains($request['messages'][0]['content'][0]['text'], 'Réponds uniquement en JSON strict')
            && $request['messages'][0]['content'][1]['type'] === 'image_url'
            && str_starts_with($request['messages'][0]['content'][1]['image_url']['url'], 'data:image/')
        );
        $this->assertDatabaseHas('book_fields', [
            'book_id' => $book->id,
            'field_key' => 'author',
            'value' => 'A. Mouchot',
            'origin' => 'ai_visible',
            'is_validated' => false,
        ]);
        $this->assertDatabaseHas('book_fields', [
            'book_id' => $book->id,
            'field_key' => 'publication_date',
            'value' => '1869',
            'origin' => 'ai_visible',
            'is_validated' => false,
        ]);
        $this->assertNotNull($book->images()->firstOrFail()->fresh()->optimized_path);
        $this->assertDatabaseMissing('book_fields', [
            'book_id' => $book->id,
            'field_key' => 'pagination',
            'value' => 'vii-238',
        ]);
        $this->assertDatabaseHas('book_fields', [
            'book_id' => $book->id,
            'field_key' => 'publisher_address',
            'value' => null,
            'origin' => 'ai_visible',
            'is_validated' => false,
        ]);
        $this->assertDatabaseHas('ai_runs', [
            'book_id' => $book->id,
            'provider' => 'mammouth',
            'model' => 'gemini-2.5-flash-lite',
            'status' => 'succeeded',
            'input_tokens' => 1200,
            'output_tokens' => 120,
        ]);
    }

    public function test_ai_run_debug_panel_is_hidden_by_default_and_visible_when_enabled(): void
    {
        Storage::fake('local');
        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 800, 1200),
        ]);
        $book = \App\Models\Book::query()->firstOrFail();
        $book->aiRuns()->create([
            'run_type' => 'title_page_extraction',
            'provider' => 'mammouth',
            'model' => 'gemini-2.5-flash-lite',
            'status' => 'cached',
            'input_tokens' => 0,
            'output_tokens' => 0,
            'cache_key' => str_repeat('a', 64),
            'cached_from_ai_run_id' => null,
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        config(['services.antiqscan_ai.show_run_debug' => false]);
        $this->get("/books/{$book->id}")
            ->assertOk()
            ->assertDontSee('Diagnostic IA — phase de test')
            ->assertDontSee('gemini-2.5-flash-lite');

        config(['services.antiqscan_ai.show_run_debug' => true]);
        $this->get("/books/{$book->id}")
            ->assertOk()
            ->assertSee('Diagnostic IA — phase de test')
            ->assertSee('gemini-2.5-flash-lite')
            ->assertSee('0 in / 0 out')
            ->assertSee('cached');
    }

    public function test_repeated_ai_extraction_reuses_cache_without_second_api_call(): void
    {
        config([
            'services.antiqscan_ai.base_url' => 'https://api.mammouth.ai/v1',
            'services.antiqscan_ai.api_key' => 'test-key',
            'services.antiqscan_ai.model' => 'gemini-2.5-flash-lite',
            'services.antiqscan_ai.max_output_tokens' => 450,
        ]);
        Http::fake([
            'api.mammouth.ai/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'fields' => [
                                'title' => 'Titre extrait une seule fois',
                                'place' => 'Paris',
                            ],
                        ]),
                    ],
                ]],
                'usage' => ['prompt_tokens' => 900, 'completion_tokens' => 50],
            ], 200),
        ]);
        Storage::fake('local');

        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 1200, 1600),
        ]);

        $book = \App\Models\Book::query()->firstOrFail();

        $this->post("/books/{$book->id}/extract/ai")->assertRedirect("/books/{$book->id}");
        $book->fields()->where('field_key', 'title')->update(['value' => 'à remplacer depuis cache']);
        $this->post("/books/{$book->id}/extract/ai")->assertRedirect("/books/{$book->id}");

        Http::assertSentCount(1);
        $firstRun = \App\Models\AiRun::query()->where('status', 'succeeded')->firstOrFail();
        $this->assertDatabaseHas('ai_runs', [
            'book_id' => $book->id,
            'status' => 'cached',
            'cached_from_ai_run_id' => $firstRun->id,
            'input_tokens' => 0,
            'output_tokens' => 0,
        ]);
        $this->assertDatabaseHas('book_fields', [
            'book_id' => $book->id,
            'field_key' => 'title',
            'value' => 'Titre extrait une seule fois',
            'origin' => 'ai_visible',
            'is_validated' => false,
        ]);
    }

    public function test_source_search_requires_validated_title(): void
    {
        Storage::fake('local');
        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 800, 1200),
        ]);
        $book = \App\Models\Book::query()->firstOrFail();

        $this->post("/books/{$book->id}/sources/search")
            ->assertRedirect("/books/{$book->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseCount('book_sources', 0);
    }

    public function test_source_candidates_are_created_and_must_be_approved_before_export(): void
    {
        config(['services.antiqscan_sources.provider' => 'mock']);
        Storage::fake('local');
        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 800, 1200),
        ]);
        $book = \App\Models\Book::query()->firstOrFail();
        $book->fields()->where('field_key', 'title')->update([
            'value' => 'La chaleur solaire',
            'is_validated' => true,
            'origin' => 'user_validated',
        ]);

        $this->post("/books/{$book->id}/sources/search")
            ->assertRedirect("/books/{$book->id}")
            ->assertSessionHas('status');

        $source = \App\Models\BookSource::query()->firstOrFail();
        $this->get("/books/{$book->id}/export/markdown")
            ->assertOk()
            ->assertDontSee('Source candidate — La chaleur solaire');

        $this->post("/books/{$book->id}/sources/{$source->id}/approve")
            ->assertRedirect("/books/{$book->id}");

        $this->get("/books/{$book->id}/export/markdown")
            ->assertOk()
            ->assertSee('### Sources validées', false)
            ->assertSee('La chaleur solaire');
    }
}
