<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkBookExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_export_selected_books_as_one_csv_file(): void
    {
        $owner = User::factory()->create();
        $firstBook = $this->validatedBookFor($owner, 'Premier ouvrage');
        $secondBook = $this->validatedBookFor($owner, 'Second ouvrage');

        $this->actingAs($owner)
            ->post(route('books.export.bulk.csv'), ['book_ids' => [$firstBook->id, $secondBook->id]])
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('content-disposition', 'attachment; filename=antiqscan-selection.csv')
            ->assertSee('Premier ouvrage')
            ->assertSee('Second ouvrage');
    }

    public function test_owner_can_export_selected_books_as_one_pdf_file(): void
    {
        $owner = User::factory()->create();
        $book = $this->validatedBookFor($owner, 'Ouvrage en PDF');

        $this->actingAs($owner)
            ->post(route('books.export.bulk.pdf'), ['book_ids' => [$book->id]])
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=antiqscan-selection.pdf');
    }

    public function test_csv_export_neutralizes_spreadsheet_formulas(): void
    {
        $owner = User::factory()->create();
        $book = $this->validatedBookFor($owner, '=HYPERLINK("https://example.test")');

        $this->actingAs($owner)
            ->post(route('books.export.bulk.csv'), ['book_ids' => [$book->id]])
            ->assertOk()
            ->assertSee("'=HYPERLINK", false);
    }

    public function test_owner_cannot_export_another_users_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherBook = $this->validatedBookFor($otherUser, 'Fiche privée');

        $this->actingAs($owner)
            ->post(route('books.export.bulk.csv'), ['book_ids' => [$otherBook->id]])
            ->assertForbidden();
    }

    public function test_export_requires_at_least_one_selected_book(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->post(route('books.export.bulk.csv'), ['book_ids' => []])
            ->assertSessionHasErrors('book_ids');
    }

    private function validatedBookFor(User $user, string $title): Book
    {
        $book = Book::factory()->for($user)->create(['user_validated_at' => now()]);
        $book->fields()->create([
            'field_key' => 'title',
            'label' => 'Titre',
            'value' => $title,
            'origin' => 'user_validated',
            'is_validated' => true,
            'is_editable' => true,
        ]);

        return $book;
    }
}
