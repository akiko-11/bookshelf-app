<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BookIsbnController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\MyReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RankController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewLikeController;
use App\Models\ReadingPlan;
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

Route::get('/reports', [MyReportController::class, 'index'])
    ->middleware('auth')
    ->name('reports.index');

// 読書計画の共通ルートモデルバインディング
Route::bind('plan', function ($id) {
    $plan = ReadingPlan::find($id);

    if ($plan === null) {
        abort(404, '読書計画が見つかりません。');
    }

    return $plan;
});

Route::get('/reading-plans', [ReadingPlanController::class, 'index'])
    ->middleware('auth')
    ->name('reading-plans.index');

Route::get('/reading-plans/create', [ReadingPlanController::class, 'create'])
    ->middleware('auth')
    ->name('reading-plans.create');

Route::post('/reading-plans', [ReadingPlanController::class, 'store'])
    ->middleware('auth')
    ->name('reading-plans.store');

Route::post('/reading-plans/{plan}/complete', [ReadingPlanController::class, 'complete'])
    ->middleware('auth')
    ->name('reading-plans.complete');

Route::get('/reading-plans/{plan}/edit', [ReadingPlanController::class, 'edit'])
    ->middleware('auth')
    ->name('reading-plans.edit');

Route::put('/reading-plans/{plan}', [ReadingPlanController::class, 'update'])
    ->middleware('auth')
    ->name('reading-plans.update');

Route::delete('/reading-plans/{plan}', [ReadingPlanController::class, 'destroy'])
    ->middleware('auth')
    ->name('reading-plans.destroy');

Route::get('/notifications', [NotificationController::class, 'index'])
    ->middleware('auth')
    ->name('notifications.index');

Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
    ->middleware('auth')
    ->name('notifications.read');
