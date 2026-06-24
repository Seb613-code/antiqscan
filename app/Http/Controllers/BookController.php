<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookImage;
use App\Models\BookSource;
use App\Services\BibliographicSourceSearchService;
use App\Services\CatalogueSheetRenderer;
use App\Services\ImageIntakeService;
use App\Services\TitlePageAiExtractionService;
use App\Services\TitlePageEnrichmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(): View
    {
        return view('books.index', [
            'books' => Book::query()->latest('updated_at')->with(['images', 'fields'])->paginate(20),
        ]);
    }

    public function store(Request $request, ImageIntakeService $images, TitlePageAiExtractionService $extractor, TitlePageEnrichmentService $enricher): RedirectResponse
    {
        $validated = $request->validate([
            'title_page' => ['required', 'file', 'mimetypes:image/jpeg', 'mimes:jpg,jpeg', 'max:10240'],
        ]);

        $book = Book::create(['status' => 'uploaded']);
        $imageData = $images->storeTitlePage($validated['title_page']);

        $book->images()->create($imageData + [
            'role' => 'title_page',
            'sort_order' => 1,
        ]);

        $this->createCatalogueFields($book);

        try {
            $visionRun = $extractor->extract($book);
            if (in_array($visionRun->status, ['succeeded', 'cached'], true)) {
                $enricher->enrich($book->fresh(['fields', 'sources']));
            }
        } catch (\Throwable $exception) {
            return redirect()->route('books.index')
                ->with('created_book_id', $book->id)
                ->with('error', 'Fiche créée, mais extraction IA en erreur : '.$exception->getMessage());
        }

        return redirect()->route('books.index')->with('created_book_id', $book->id);
    }

    public function destroy(Book $book): RedirectResponse
    {
        $book->load('images');

        foreach ($book->images as $image) {
            Storage::disk('local')->delete(array_filter([
                $image->original_path,
                $image->optimized_path,
            ]));
        }

        $book->delete();

        return redirect()->route('books.index')->with('status', 'Fiche supprimée.');
    }

    public function image(BookImage $bookImage)
    {
        $path = $bookImage->optimized_path ?: $bookImage->original_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path));
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
            'pagination' => 'Collation',
            'binding' => 'Reliure',
            'condition' => 'État',
            'copy_notes' => 'Particularités d’exemplaire',
        ];
    }

    public function show(Book $book): View
    {
        return view('books.show', [
            'book' => $book->load(['images', 'fields', 'sources', 'priceObservations', 'aiRuns' => fn ($query) => $query->latest()->limit(1)]),
            'showAiRunDebug' => (bool) config('services.antiqscan_ai.show_run_debug', false),
        ]);
    }

    public function updateFields(Request $request, Book $book): RedirectResponse
    {
        $validated = $request->validate([
            'fields' => ['array'],
            'fields.*.label' => ['required', 'string', 'max:120'],
            'fields.*.value' => ['nullable', 'string'],
            'catalogue_note' => ['nullable', 'string'],
        ]);

        foreach ($validated['fields'] ?? [] as $id => $fieldData) {
            $book->fields()->whereKey($id)->where('is_editable', true)->update([
                'label' => $fieldData['label'],
                'value' => $fieldData['value'] ?? null,
                'is_validated' => filled($fieldData['value'] ?? null),
                'origin' => 'user_validated',
            ]);
        }

        $book->update([
            'catalogue_note' => $validated['catalogue_note'] ?? null,
            'status' => 'validated',
            'user_validated_at' => now(),
        ]);

        return back()->with('status', 'Modifications enregistrées.');
    }

    public function catalogue(Book $book, CatalogueSheetRenderer $renderer): View
    {
        $book->load(['fields' => fn ($query) => $query->orderBy('id'), 'sources' => fn ($query) => $query->orderBy('id')]);

        return view('books.catalogue', [
            'book' => $book,
            'markdown' => $renderer->renderMarkdown($book->fields, $book->sources),
        ]);
    }

    public function exportMarkdown(Book $book, CatalogueSheetRenderer $renderer): Response
    {
        $book->load(['fields' => fn ($query) => $query->orderBy('id'), 'sources' => fn ($query) => $query->orderBy('id')]);

        return response($renderer->renderMarkdown($book->fields, $book->sources), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }

    public function exportJson(Book $book, CatalogueSheetRenderer $renderer): JsonResponse
    {
        $book->load(['fields' => fn ($query) => $query->orderBy('id'), 'sources' => fn ($query) => $query->orderBy('id')]);

        return response()->json([
            'book_id' => $book->id,
            'fields' => $renderer->renderArray($book->fields),
            'sources' => $renderer->renderSourcesArray($book->sources),
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function exportCsv(Book $book, CatalogueSheetRenderer $renderer): Response
    {
        $book->load(['fields' => fn ($query) => $query->orderBy('id')]);

        return response($renderer->renderCsv($book->fields), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function searchSources(Book $book, BibliographicSourceSearchService $sources): RedirectResponse
    {
        try {
            $created = $sources->search($book);

            return redirect()->route('books.show', $book)
                ->with('status', $created.' source(s) candidate(s) ajoutée(s).');
        } catch (\Throwable $exception) {
            return redirect()->route('books.show', $book)
                ->with('error', $exception->getMessage());
        }
    }

    public function approveSource(Book $book, BookSource $source): RedirectResponse
    {
        abort_unless($source->book_id === $book->id, 404);

        $source->update(['user_approved' => true]);

        return redirect()->route('books.show', $book);
    }

    public function extractAi(Book $book, TitlePageAiExtractionService $extractor): RedirectResponse
    {
        $extractor->extract($book);

        return redirect()->route('books.show', $book);
    }
}
