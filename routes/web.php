<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

Route::get('/access', [AccessController::class, 'create'])->name('access.create');
Route::post('/access', [AccessController::class, 'store'])->name('access.store');

Route::middleware('antiqscan.access')->group(function (): void {
    Route::get('/', [BookController::class, 'index'])->name('books.index');
    Route::get('/books', [BookController::class, 'index'])->name('books.list');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
    Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
    Route::get('/book-images/{bookImage}', [BookController::class, 'image'])->name('book-images.show');
    Route::put('/books/{book}/fields', [BookController::class, 'updateFields'])->name('books.fields.update');
    Route::post('/books/{book}/extract/ai', [BookController::class, 'extractAi'])->name('books.extract.ai');
    Route::post('/books/{book}/sources/search', [BookController::class, 'searchSources'])->name('books.sources.search');
    Route::post('/books/{book}/sources/{source}/approve', [BookController::class, 'approveSource'])->name('books.sources.approve');
    Route::get('/books/{book}/catalogue', [BookController::class, 'catalogue'])->name('books.catalogue');
    Route::get('/books/{book}/export/markdown', [BookController::class, 'exportMarkdown'])->name('books.export.markdown');
    Route::get('/books/{book}/export/json', [BookController::class, 'exportJson'])->name('books.export.json');
    Route::get('/books/{book}/export/csv', [BookController::class, 'exportCsv'])->name('books.export.csv');
});
