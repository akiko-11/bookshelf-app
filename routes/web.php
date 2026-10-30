<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BookIsbnController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\RankController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewLikeController;
use Illuminate\Support\Facades\Route;

// ゲストでも閲覧可能
Route::get('/', [BookController::class, 'index'])
    ->name('books.index');

// 認証済みユーザーのみ
Route::get('/books/create', [BookController::class, 'create'])
    ->middleware('auth')
    ->name('books.create');

Route::get('/books/isbn/{isbn}', [BookIsbnController::class, 'index'])
    ->middleware('auth')
    ->name('isbn.index');

// ゲストでも閲覧可能
Route::get('/books/{book}', [BookController::class, 'show'])
    ->name('books.show');

Route::get('/books/{book}/edit', [BookController::class, 'edit'])
    ->middleware('auth')
    ->name('books.edit');

Route::delete('/books/{book}', [BookController::class, 'destroy'])
    ->middleware('auth')
    ->name('books.destroy');

Route::put('/books/{book}', [BookController::class, 'update'])
    ->middleware('auth')
    ->name('books.update');

Route::get('/ranking',
    [RankController::class, 'index'])
    ->name('ranking.index');

Route::get('/favorites',
    [FavoriteController::class, 'index'])
    ->middleware('auth')
    ->name('favorites.index');

Route::get('/genres',
    [GenreController::class, 'index'])
    ->middleware('auth')
    ->name('genres.index');

Route::get('/genres/create',
    [GenreController::class, 'create'])
    ->middleware('auth')
    ->name('genres.create');

Route::post('/genres',
    [GenreController::class, 'store'])
    ->middleware('auth')
    ->name('genres.store');

Route::get('/genres/{genre}',
    [GenreController::class, 'show'])
    ->middleware('auth')
    ->name('genres.show');

Route::get('/genres/{genre}/edit',
    [GenreController::class, 'edit'])
    ->middleware('auth')
    ->name('genres.edit');

Route::put('/genres/{genre}',
    [GenreController::class, 'update'])
    ->middleware('auth')
    ->name('genres.update');

Route::delete('/genres/{genre}',
    [GenreController::class, 'destroy'])
    ->middleware('auth')
    ->name('genres.destroy');

Route::post('/books/{book}/favorites',
    [FavoriteController::class, 'toggle'])
    ->middleware('auth')
    ->name('favorites.toggle');

Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
    ->middleware('auth')
    ->name('reviews.store');

Route::post('/reviews/{review}/like', [ReviewLikeController::class, 'toggle'])
    ->middleware('auth')
    ->name('reviews.like');

Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])
    ->middleware('auth')
    ->name('reviews.edit');

Route::put('/reviews/{review}', [ReviewController::class, 'update'])
    ->middleware('auth')
    ->name('reviews.update');

Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])
    ->middleware('auth')
    ->name('reviews.destroy');

Route::post('/books', [BookController::class, 'store'])
    ->middleware('auth')
    ->name('books.store');

Route::get('/reports', function () {
    return 'マイレポート画面は未実装です。';
})->middleware('auth')
    ->name('reports.index');

Route::get('/reading-plans', [ReadingPlanController::class, 'index'])
    ->middleware('auth')
    ->name('reading-plans.index');

// 読了処理は後続Issueで実装
Route::post('/reading-plans/{readingPlan}/complete', function () {
    abort(501, '読了機能は未実装です。');
})->middleware('auth')
    ->name('reading-plans.complete');

Route::get('/reading-plans/{plan}/edit', function () {
    abort(501, '編集ボタンは未実装です。');
})->middleware('auth')
    ->name('reading-plans.edit');

Route::delete('/reading-plans/{plan}', function () {
    abort(501, '削除ボタンは未実装です。');
})->middleware('auth')
    ->name('reading-plans.destroy');

Route::get('/reading-plans/create', function () {
    return '読書計画作成画面は未実装です。';
})->middleware('auth')
    ->name('reading-plans.create');

Route::get('/notifications', function () {
    return '通知一覧画面は未実装です。';
})->middleware('auth')
    ->name('notifications.index');
