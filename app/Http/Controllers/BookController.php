<?php

namespace App\Http\Controllers;

use App\Models\AiRun;
use App\Models\Book;
use App\Services\CatalogueSheetRenderer;
use App\Services\ImageIntakeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(): View
    {
        return view('books.index', [
            'books' => Book::query()->latest()->with('images')->paginate(20),
        ]);
    }

    public function store(Request $request, ImageIntakeService $images): RedirectResponse
    {
        $validated = $request->validate([
            'title_page' => ['required', 'image', 'max:10240'],
        ]);

        $book = Book::create(['status' => 'uploaded']);
        $imageData = $images->storeTitlePage($validated['title_page']);

        $book->images()->create($imageData + [
            'role' => 'title_page',
            'sort_order' => 1,
        ]);

        $this->createCatalogueFields($book);

        return redirect()->route('books.show', $book);
    }

    private function createCatalogueFields(Book $book): void
    {
        foreach ($this->visibleTitlePageFields() as $key => $label) {
            $book->fields()->create([
                'field_key' => $key,
                'label' => $label,
                'value' => null,
                'origin' => 'ai_visible',
                'confidence' => null,
                'is_validated' => false,
                'is_editable' => true,
            ]);
        }

        foreach ($this->manualPhysicalFields() as $key => $label) {
            $book->fields()->create([
                'field_key' => $key,
                'label' => $label,
                'value' => null,
                'origin' => 'user_manual',
                'confidence' => null,
                'is_validated' => false,
                'is_editable' => true,
            ]);
        }
    }

    private function visibleTitlePageFields(): array
    {
        return [
            'author' => 'Auteur',
            'title' => 'Titre',
            'subtitle' => 'Sous-titre',
            'place' => 'Lieu',
            'publisher' => 'Éditeur / imprimeur',
            'publisher_address' => 'Adresse éditeur',
            'publication_date' => 'Date',
            'illustration_statement' => 'Mention d’illustrations',
            'edition_statement' => 'Mention d’édition visible',
            'visible_notes' => 'Notes visibles',
        ];
    }

    private function manualPhysicalFields(): array
    {
        return [
            'format' => 'Format',
            'dimensions' => 'Dimensions',
            'pagination' => 'Pagination',
            'binding' => 'Reliure',
            'condition' => 'État',
            'copy_notes' => 'Particularités d’exemplaire',
        ];
    }

    public function show(Book $book): View
    {
        return view('books.show', [
            'book' => $book->load(['images', 'fields', 'sources', 'priceObservations']),
        ]);
    }

    public function updateFields(Request $request, Book $book): RedirectResponse
    {
        $validated = $request->validate([
            'fields' => ['array'],
            'fields.*.label' => ['required', 'string', 'max:120'],
            'fields.*.value' => ['nullable', 'string'],
            'fields.*.is_validated' => ['nullable', 'boolean'],
        ]);

        foreach ($validated['fields'] ?? [] as $id => $fieldData) {
            $book->fields()->whereKey($id)->where('is_editable', true)->update([
                'label' => $fieldData['label'],
                'value' => $fieldData['value'] ?? null,
                'is_validated' => (bool) ($fieldData['is_validated'] ?? false),
                'origin' => 'user_validated',
            ]);
        }

        $book->update(['status' => 'validated', 'user_validated_at' => now()]);

        return back();
    }

    public function catalogue(Book $book, CatalogueSheetRenderer $renderer): View
    {
        $book->load(['fields' => fn ($query) => $query->orderBy('id')]);

        return view('books.catalogue', [
            'book' => $book,
            'markdown' => $renderer->renderMarkdown($book->fields),
        ]);
    }

    public function exportMarkdown(Book $book, CatalogueSheetRenderer $renderer): Response
    {
        $book->load(['fields' => fn ($query) => $query->orderBy('id')]);

        return response($renderer->renderMarkdown($book->fields), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }

    public function exportJson(Book $book, CatalogueSheetRenderer $renderer): JsonResponse
    {
        $book->load(['fields' => fn ($query) => $query->orderBy('id')]);

        return response()->json([
            'book_id' => $book->id,
            'fields' => $renderer->renderArray($book->fields),
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function exportCsv(Book $book, CatalogueSheetRenderer $renderer): Response
    {
        $book->load(['fields' => fn ($query) => $query->orderBy('id')]);

        return response($renderer->renderCsv($book->fields), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function extractMock(Book $book): RedirectResponse
    {
        $values = [
            'author' => 'A. Mouchot',
            'title' => 'La chaleur solaire et ses applications industrielles',
            'illustration_statement' => '35 gravures intercalées dans le texte',
            'place' => 'Paris',
            'publisher' => 'Gauthier-Villars',
            'publisher_address' => '55, Quai des Augustins, 55',
            'publication_date' => '1869',
        ];

        foreach ($values as $key => $value) {
            $book->fields()->where('field_key', $key)->update([
                'value' => $value,
                'origin' => 'ai_visible',
                'confidence' => 1,
                'is_validated' => false,
            ]);
        }

        AiRun::create([
            'book_id' => $book->id,
            'run_type' => 'title_page_extraction',
            'provider' => 'mock',
            'model' => 'mouchot-fixture',
            'status' => 'succeeded',
            'prompt' => ['fixture' => 'mouchot'],
            'response' => $values,
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        $book->update(['status' => 'extracted']);

        return redirect()->route('books.show', $book);
    }
}
