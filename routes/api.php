<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FanController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\MemoryController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\RegisterController;

// All routes that deal with registration or login, or that need authentification will have the prefix 'auth'
Route::prefix('auth')->group(function () {
    // Public Endpoints
    Route::post('/register', RegisterController::class);
    Route::post('/login', LoginController::class);

    // Secure Endpoints - needing authentication
    Route::controller()->middleware('auth:sanctum')->group(function () {
        Route::post('/logout', LogoutController::class);

        Route::controller(MemoryController::class)->group(function () {
            Route::get('/memories', 'index');
            Route::get('/memory/{title}', 'show');
            Route::get('/memories/{kid}', 'index');
            Route::post('/memory', 'create');
            Route::patch('/memory/{title}', 'update');
            Route::delete('/memory/{title}', 'delete');
        });

        Route::controller(CommentController::class)->group(function () {
            Route::post('/memory/{title}/comment', 'create');
            Route::patch('/memory/{title}/comment/{id}', 'update');
            Route::delete('/memory/{title}/comment/{id}', 'delete');
        });

        Route::controller(FileController::class)->group(function () {
            Route::get('/file/{title}', 'show')->whereNumber('id');
            Route::delete('/file/{title}', 'delete')->whereNumber('id');
            Route::post('/file/{title}', 'update')->whereNumber('id');
        });

        Route::controller(SearchController::class)->group(function () {
            Route::get('/search/{category}/{keyword}', 'index');
            Route::get('/search/{title}', 'index');
            Route::get('/search/{date}', 'index');
        });

        // ADMIN & profile owners
        Route::controller(FanController::class)->group(function () {
            // Only 'admin' (set manually on DB) can see the fans list
            Route::get('/fans', 'fansList');

            // Only 'admin' or owner can update their info and if need be, delete profile
            Route::get('/fan/{id}', 'getById');
            Route::patch('/fan/{id}', 'update');
            Route::delete('/fan/{id}', 'delete');
        });
    });
});

// 404
Route::fallback(function () {
    return response()->json(['message' => 'Unbekantes Ziel'], 404);
});
