<?php

use App\Http\Controllers\Api\V1\BookController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/books', [BookController::class, 'index']);
