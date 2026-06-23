<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\CatalogueSheetRenderer;
use App\Services\ImageIntakeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $this->createManualCatalogueFields($book);

        return redirect()->route('books.show', $book);
    }

    private function createManualCatalogueFields(Book $book): void
    {
        collect([
            'format' => 'Format',
            'dimensions' => 'Dimensions',
            'pagination' => 'Pagination',
            'binding' => 'Reliure',
            'condition' => 'État',
            'copy_notes' => 'Particularités d’exemplaire',
        ])->each(fn (string $label, string $key) => $book->fields()->create([
            'field_key' => $key,
            'label' => $label,
            'value' => null,
            'origin' => 'user_manual',
            'confidence' => null,
            'is_validated' => false,
            'is_editable' => true,
        ]));
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
}
