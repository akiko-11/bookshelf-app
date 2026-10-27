<?php

use App\Http\Controllers\Api\V1\BookController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/books', [BookController::class, 'index']);

Route::get('/v1/books/{book}', [BookController::class, 'show'])
    ->missing(function () {
        return response()->json([
            'message' => '指定された書籍が見つかりません。',
        ], 404);
    });

Route::post('/v1/books', [BookController::class, 'store']);
