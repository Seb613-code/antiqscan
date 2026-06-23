<?php

use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BookController::class, 'index'])->name('books.index');
Route::post('/books', [BookController::class, 'store'])->name('books.store');
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
Route::put('/books/{book}/fields', [BookController::class, 'updateFields'])->name('books.fields.update');
Route::post('/books/{book}/extract/mock', [BookController::class, 'extractMock'])->name('books.extract.mock');
Route::get('/books/{book}/catalogue', [BookController::class, 'catalogue'])->name('books.catalogue');
Route::get('/books/{book}/export/markdown', [BookController::class, 'exportMarkdown'])->name('books.export.markdown');
