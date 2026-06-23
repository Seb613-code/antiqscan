<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
            ->assertSee('Page de titre');
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

        $this->get("/books/{$book->id}/export/markdown")
            ->assertOk()
            ->assertHeader('content-type', 'text/markdown; charset=UTF-8')
            ->assertSee('**Titre** — La chaleur solaire', false)
            ->assertDontSee('999 € non validé');
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
            ->assertSee('Auteur')
            ->assertSee('Format');
    }
}
