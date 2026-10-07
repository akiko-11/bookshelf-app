<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

// ゲストでも閲覧可能
Route::get('/', [BookController::class, 'index'])
    ->name('books.index');

// 認証済みユーザーのみ
Route::get('/books/create', [BookController::class, 'create'])
    ->middleware('auth')
    ->name('books.create');

// ゲストでも閲覧可能
Route::get('/books/{book}', [BookController::class, 'show'])
    ->name('books.show');

Route::get('/ranking', function () {
    return 'ランキング画面は未実装です';
})->name('ranking.index');

Route::get('/favorites', function () {
    return 'お気に入り画面は未実装です';
})->name('favorites.index');

Route::get('/genres', function () {
    return 'ジャンル画面は未実装です';
})->name('genres.index');

Route::post('/books/{book}/favorites',
    [FavoriteController::class, 'toggle'])
    ->middleware('auth')
    ->name('favorites.toggle');

Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
    ->middleware('auth')
    ->name('reviews.store');

Route::post('/reviews/{review}/like', function () {
    return 'いいねボタンは未実装です';
})
    ->middleware('auth')
    ->name('reviews.like');

Route::post('/books', [BookController::class, 'store'])
    ->middleware('auth')
    ->name('books.store');
