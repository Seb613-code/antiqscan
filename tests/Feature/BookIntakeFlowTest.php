<?php

namespace Tests\Feature;

use App\Models\AiRun;
use App\Models\Book;
use App\Models\BookImage;
use App\Models\BookSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookIntakeFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.antiqscan_ai.base_url' => 'https://api.mammouth.ai/v1',
            'services.antiqscan_ai.api_key' => 'test-key',
            'services.antiqscan_ai.model' => 'gemini-2.5-flash-lite',
            'services.antiqscan_ai.max_output_tokens' => 450,
            'services.antiqscan_enrichment.base_url' => 'https://api.mammouth.ai/v1',
            'services.antiqscan_enrichment.api_key' => 'test-key',
            'services.antiqscan_enrichment.model' => 'sonar',
            'services.antiqscan_enrichment.max_output_tokens' => 1200,
        ]);

        $this->actingAs(User::factory()->create());

        Http::fake(fn ($request) => str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'bibliographique')
            ? Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'display_title' => 'MOUCHOT, Chaleur solaire, 1869,',
                            'author' => 'MOUCHOT, Augustin',
                            'title' => 'La chaleur solaire et ses applications industrielles',
                            'publisher' => 'Gauthier-villars',
                            'place' => 'Paris',
                            'publication_date' => '1869',
                            'academic_notice' => 'Notice factuelle sourcée [1].',
                            'sources' => [[
                                'title' => 'BnF notice',
                                'url' => 'https://catalogue.bnf.fr/ark:/12148/cb00000000',
                                'citation' => 'Bibliothèque nationale de France.',
                            ]],
                        ]),
                    ],
                ]],
                'usage' => ['prompt_tokens' => 300, 'completion_tokens' => 90],
            ], 200)
            : Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['fields' => ['title' => 'Titre extrait automatiquement']]),
                    ],
                ]],
                'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 20],
            ], 200)
        );
    }

    public function test_home_page_shows_review_card_and_filterable_library_table(): void
    {
        Book::factory()->create();

        $this->get('/')
            ->assertOk()
            ->assertSee('Fiches à relire')
            ->assertSee('Votre bibliothèque')
            ->assertSee('Auteur')
            ->assertSee('Titre')
            ->assertSee('Date')
            ->assertSee('Rechercher une fiche')
            ->assertSee('Filtres')
            ->assertSee('Date de publication')
            ->assertSee('Du')
            ->assertSee('Au')
            ->assertSee('Trier par auteur, croissant')
            ->assertSee('Trier par auteur, décroissant')
            ->assertSee('Export')
            ->assertSee('Export CSV')
            ->assertSee('Export PDF')
            ->assertSee('name="book_ids[]"', false)
            ->assertDontSee('Fiches validées');
    }

    public function test_home_page_shows_upload_and_review_photo_previews(): void
    {
        $book = Book::factory()->create();
        $image = $book->images()->create([
            'role' => 'title_page',
            'sort_order' => 1,
            'original_path' => 'books/title-page.jpg',
            'optimized_path' => 'books/title-page-optimized.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 12_000,
            'width' => 800,
            'height' => 1200,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('id="title-page-preview"', false)
            ->assertSee('Aperçu de la page de titre sélectionnée')
            ->assertSee(route('book-images.show', $image), false)
            ->assertSee('Aperçu de la page de titre de la fiche à relire')
            ->assertDontSee('title-page-zoom', false)
            ->assertDontSee('Survolez l’image pour l’agrandir.');
    }

    public function test_home_page_shows_an_authenticated_users_library(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('AntIqscan')
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
        $this->assertDatabaseHas('books', ['status' => 'extracted']);
        $this->assertDatabaseHas('book_images', ['role' => 'title_page', 'sort_order' => 1]);

        $image = BookImage::query()->firstOrFail();
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
        $book = Book::factory()->create(['status' => 'extracted']);
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

    public function test_catalogue_sheet_hides_unvalidated_ai_notice_but_keeps_it_for_validated_record(): void
    {
        $draftBook = Book::factory()->create([
            'status' => 'extracted',
            'catalogue_note' => 'Notice IA non validée.',
            'user_validated_at' => null,
        ]);

        $this->get("/books/{$draftBook->id}/catalogue")
            ->assertOk()
            ->assertDontSee('Notice IA non validée.', false);

        $validatedBook = Book::factory()->create([
            'status' => 'validated',
            'catalogue_note' => 'Notice factuelle validée.',
            'user_validated_at' => now(),
        ]);

        $this->get("/books/{$validatedBook->id}/catalogue")
            ->assertOk()
            ->assertSee('Notice factuelle validée.', false);
    }

    public function test_catalogue_sheet_renders_a_structured_notice_from_validated_data_only(): void
    {
        $book = Book::factory()->create([
            'status' => 'validated',
            'catalogue_note' => 'Notice factuelle validée.',
            'user_validated_at' => now(),
        ]);

        foreach ([
            ['author', 'Auteur', 'A. Mouchot'],
            ['title', 'Titre', 'La chaleur solaire'],
            ['place', 'Lieu', 'Paris'],
            ['publisher', 'Éditeur', 'Gauthier-Villars'],
            ['publication_date', 'Date', '1869'],
            ['pagination', 'Collation', 'VII-238 p.'],
            ['binding', 'Reliure', 'Demi-chagrin'],
        ] as [$key, $label, $value]) {
            $book->fields()->create([
                'field_key' => $key,
                'label' => $label,
                'value' => $value,
                'origin' => 'user_validated',
                'is_validated' => true,
                'is_editable' => true,
            ]);
        }

        $book->fields()->create([
            'field_key' => 'condition',
            'label' => 'État',
            'value' => 'Information non validée',
            'origin' => 'ai_visible',
            'is_validated' => false,
            'is_editable' => true,
        ]);

        $this->get("/books/{$book->id}/catalogue")
            ->assertOk()
            ->assertSee('MOUCHOT, La chaleur solaire, 1869', false)
            ->assertDontSee('MOUCHOT, La chaleur solaire, 1869,', false)
            ->assertSee('Publication')
            ->assertSee('Collation')
            ->assertSee('Reliure, état et particularités')
            ->assertSee('Notice')
            ->assertSee('Notice factuelle validée.', false)
            ->assertDontSee('Information non validée');
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

    private function bookWithOneValidatedAndOneUnvalidatedField(): Book
    {
        $book = Book::factory()->create(['status' => 'validated']);
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

    public function test_book_show_page_starts_with_image_card_and_no_extraction_buttons(): void
    {
        Storage::fake('local');

        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 1200, 1600),
        ]);

        $book = Book::query()->firstOrFail();

        $this->get("/books/{$book->id}")
            ->assertOk()
            ->assertSee('Image')
            ->assertSee('height="300"', false)
            ->assertSee('object-contain')
            ->assertSee('title-page-zoom', false)
            ->assertSee('title-page-zoom__magnified', false)
            ->assertSee('Survolez l’image pour l’agrandir.')
            ->assertSee('data-zoom-scale="2"', false)
            ->assertSee('Informations bibliographiques repérées')
            ->assertSee('Notice de catalogue')
            ->assertSee('Notice factuelle sourcée.')
            ->assertSee('Description matérielle')
            ->assertSee('Sources à vérifier')
            ->assertSee('Enregistrer les modifications')
            ->assertDontSee('Valider les champs remplis')
            ->assertDontSee('Enregistrer les validations')
            ->assertDontSee('Export Markdown')
            ->assertDontSee('Export JSON')
            ->assertDontSee('ai_enriched')
            ->assertDontSee('type="checkbox"', false)
            ->assertDontSee('Étape 2 — lancer l’extraction')
            ->assertDontSee('Lancer l’extraction IA réelle')
            ->assertDontSee('Tester avec le mock Mouchot')
            ->assertDontSee('1. Image importée')
            ->assertSee('Auteur')
            ->assertSee('Format')
            ->assertSee('Collation')
            ->assertDontSee('Pagination');
    }

    public function test_saving_book_form_records_editable_fields_without_validation_checkboxes(): void
    {
        Storage::fake('local');

        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 1200, 1600),
        ]);

        $book = Book::query()->firstOrFail();
        $title = $book->fields()->where('field_key', 'title')->firstOrFail();
        $subtitle = $book->fields()->where('field_key', 'subtitle')->firstOrFail();

        $this->put("/books/{$book->id}/fields", [
            'catalogue_note' => 'Notice corrigée par utilisateur.',
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
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'catalogue_note' => 'Notice corrigée par utilisateur.',
        ]);

        $this->get("/books/{$book->id}")
            ->assertOk()
            ->assertSee('La chaleur solaire')
            ->assertSee('Sous-titre')
            ->assertSee('Enregistrer les modifications')
            ->assertDontSee('type="checkbox"', false);
    }

    public function test_home_page_shows_library_actions_for_existing_books(): void
    {
        Storage::fake('local');

        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 1200, 1600),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('À relire')
            ->assertSee('Relire la fiche')
            ->assertSee('Supprimer la fiche')
            ->assertSee('Voir la fiche')
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
            ->assertSee('À relire')
            ->assertSee('Relire la fiche')
            ->assertSee('Supprimer la fiche')
            ->assertDontSee('Lancer l’extraction');
    }

    public function test_home_page_shows_review_card_and_library_rows_with_next_actions(): void
    {
        $reviewBook = Book::factory()->create([
            'status' => 'extracted',
            'working_title' => 'MOUCHOT, Chaleur solaire, 1869,',
            'user_validated_at' => null,
        ]);
        $validatedBook = Book::factory()->create([
            'status' => 'validated',
            'working_title' => 'SEGUIN, Chemins de fer, 1839,',
            'user_validated_at' => now(),
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Importer')
            ->assertSee('Relire')
            ->assertSee('À relire')
            ->assertSee('Relire la fiche')
            ->assertSee('Voir la fiche')
            ->assertDontSee('Fiches validées')
            ->assertSee(route('books.show', $reviewBook), false)
            ->assertSee(route('books.show', $validatedBook), false);
    }

    public function test_created_book_flash_message_explicitly_points_to_review_step(): void
    {
        Storage::fake('local');

        $response = $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 1200, 1600),
        ]);

        $response->assertRedirect();

        $book = Book::query()->firstOrFail();

        $this->get('/')
            ->assertOk()
            ->assertSee('Fiche créée.')
            ->assertSee('Les champs extraits doivent maintenant être relus avant consultation du catalogue.')
            ->assertSee('Relire la fiche')
            ->assertSee(route('books.show', $book), false);
    }

    public function test_book_show_page_uses_documentary_review_headings(): void
    {
        Storage::fake('local');

        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 1200, 1600),
        ]);

        $book = Book::query()->firstOrFail();

        $this->get("/books/{$book->id}")
            ->assertOk()
            ->assertSee('Informations bibliographiques repérées')
            ->assertSee('Notice de catalogue')
            ->assertSee('Description matérielle')
            ->assertSee('Sources à vérifier')
            ->assertDontSee('Champs visibles sur page de titre')
            ->assertDontSee('Notice académique générée par l’étape 2, modifiable.')
            ->assertDontSee('Champs physiques manuels');
    }

    public function test_catalogue_page_uses_documentary_sheet_shell(): void
    {
        $book = Book::factory()->create([
            'status' => 'validated',
            'working_title' => 'MOUCHOT, Chaleur solaire, 1869,',
            'catalogue_note' => 'Notice factuelle validée.',
            'user_validated_at' => now(),
        ]);
        $book->fields()->create([
            'field_key' => 'title',
            'label' => 'Titre',
            'value' => 'La chaleur solaire',
            'origin' => 'user_validated',
            'is_validated' => true,
            'is_editable' => true,
        ]);

        $this->get("/books/{$book->id}/catalogue")
            ->assertOk()
            ->assertSee('Fiche catalogue validée')
            ->assertSee('Voir le catalogue')
            ->assertSee('document-sheet', false);
    }

    public function test_catalogue_cta_and_screen_copy_follow_validation_state(): void
    {
        $draftBook = Book::factory()->create([
            'status' => 'extracted',
            'working_title' => 'MOUCHOT, Chaleur solaire, 1869,',
            'user_validated_at' => null,
        ]);
        $validatedBook = Book::factory()->create([
            'status' => 'validated',
            'working_title' => 'SEGUIN, Chemins de fer, 1839,',
            'user_validated_at' => now(),
        ]);

        $this->get("/books/{$draftBook->id}")
            ->assertOk()
            ->assertSee('Aperçu de catalogue')
            ->assertDontSee('Voir fiche catalogue');

        $this->get("/books/{$validatedBook->id}")
            ->assertOk()
            ->assertSee('Voir le catalogue')
            ->assertDontSee('Aperçu de catalogue');

        $this->get("/books/{$draftBook->id}/catalogue")
            ->assertOk()
            ->assertSee('Aperçu de catalogue')
            ->assertSee('Brouillon non validé')
            ->assertDontSee('Fiche catalogue validée');

        $this->get("/books/{$validatedBook->id}/catalogue")
            ->assertOk()
            ->assertSee('Voir le catalogue')
            ->assertSee('Fiche catalogue validée')
            ->assertDontSee('Brouillon non validé');
    }

    public function test_create_book_launches_ai_extraction_immediately(): void
    {
        Storage::fake('local');

        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 1200, 1600),
        ])->assertRedirect();

        $book = Book::query()->firstOrFail();

        $this->assertDatabaseHas('ai_runs', [
            'book_id' => $book->id,
            'run_type' => 'title_page_extraction',
            'provider' => 'mammouth',
            'model' => 'gemini-2.5-flash-lite',
            'status' => 'succeeded',
        ]);
        $this->assertDatabaseHas('book_fields', [
            'book_id' => $book->id,
            'field_key' => 'title',
            'value' => 'La chaleur solaire et ses applications industrielles',
            'origin' => 'ai_enriched',
            'is_validated' => false,
        ]);
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'status' => 'extracted',
            'working_title' => 'MOUCHOT, Chaleur solaire, 1869,',
            'catalogue_note' => 'Notice factuelle sourcée.',
        ]);
        $this->assertDatabaseHas('ai_runs', [
            'book_id' => $book->id,
            'run_type' => 'title_page_enrichment',
            'provider' => 'mammouth',
            'model' => 'sonar',
            'status' => 'succeeded',
        ]);
        $this->assertDatabaseHas('book_sources', [
            'book_id' => $book->id,
            'source_type' => 'ai_enrichment',
            'title' => 'BnF notice',
            'user_approved' => false,
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

        $book = Book::query()->firstOrFail();

        Http::assertSent(fn ($request) => $request->url() === 'https://api.mammouth.ai/v1/chat/completions'
            && $request['model'] === 'gemini-2.5-flash-lite'
            && $request['max_tokens'] === 450
            && $request['messages'][0]['content'][0]['type'] === 'text'
            && str_contains($request['messages'][0]['content'][0]['text'], 'Réponds uniquement en JSON strict')
            && $request['messages'][0]['content'][1]['type'] === 'image_url'
            && str_starts_with($request['messages'][0]['content'][1]['image_url']['url'], 'data:image/')
        );
        $this->assertDatabaseHas('ai_runs', [
            'book_id' => $book->id,
            'provider' => 'mammouth',
            'model' => 'gemini-2.5-flash-lite',
            'status' => 'succeeded',
            'input_tokens' => 100,
            'output_tokens' => 20,
        ]);
        $this->assertNotNull($book->images()->firstOrFail()->fresh()->optimized_path);
        $this->assertDatabaseMissing('book_fields', [
            'book_id' => $book->id,
            'field_key' => 'pagination',
            'value' => 'vii-238',
        ]);

    }

    public function test_ai_run_debug_panel_is_hidden_by_default_and_visible_when_enabled(): void
    {
        Storage::fake('local');
        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 800, 1200),
        ]);
        $book = Book::query()->firstOrFail();
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
            ->assertSee('100 in / 20 out')
            ->assertSee('succeeded');
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

        $book = Book::query()->firstOrFail();

        $book->fields()->where('field_key', 'title')->update(['value' => 'à remplacer depuis cache']);
        $this->post("/books/{$book->id}/extract/ai")->assertRedirect("/books/{$book->id}");

        Http::assertSentCount(2);
        $firstRun = AiRun::query()->where('status', 'succeeded')->firstOrFail();
        $this->assertDatabaseHas('ai_runs', [
            'book_id' => $book->id,
            'status' => 'cached',
            'cached_from_ai_run_id' => $firstRun->id,
            'input_tokens' => 0,
            'output_tokens' => 0,
        ]);

    }

    public function test_source_search_requires_validated_title(): void
    {
        Storage::fake('local');
        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 800, 1200),
        ]);
        $book = Book::query()->firstOrFail();

        $this->post("/books/{$book->id}/sources/search")
            ->assertRedirect("/books/{$book->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseCount('book_sources', 1);
    }

    public function test_source_candidates_are_created_and_must_be_approved_before_export(): void
    {
        config(['services.antiqscan_sources.provider' => 'mock']);
        Storage::fake('local');
        $this->post('/books', [
            'title_page' => UploadedFile::fake()->image('titre.jpg', 800, 1200),
        ]);
        $book = Book::query()->firstOrFail();
        $book->fields()->where('field_key', 'title')->update([
            'value' => 'La chaleur solaire',
            'is_validated' => true,
            'origin' => 'user_validated',
        ]);

        $this->post("/books/{$book->id}/sources/search")
            ->assertRedirect("/books/{$book->id}")
            ->assertSessionHas('status');

        $source = BookSource::query()->firstOrFail();
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
