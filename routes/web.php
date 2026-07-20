<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/inscription', [AuthController::class, 'createRegister'])->name('register.create');
    Route::post('/inscription', [AuthController::class, 'storeRegister'])->name('register.store');
    Route::get('/connexion', [AuthController::class, 'createLogin'])->name('login');
    Route::post('/connexion', [AuthController::class, 'storeLogin'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/verifier-email', fn () => view('auth.verify-email'))->name('verification.notice');
    Route::get('/verifier-email/{id}/{hash}', function (EmailVerificationRequest $request): RedirectResponse {
        $request->fulfill();

        return redirect()->route('books.index');
    })->middleware('signed')->name('verification.verify');
    Route::post('/verifier-email/renvoyer', function (Request $request): RedirectResponse {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Lien de vérification renvoyé.');
    })->middleware('throttle:6,1')->name('verification.send');
    Route::post('/deconnexion', [AuthController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
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
