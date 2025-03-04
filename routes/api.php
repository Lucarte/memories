<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UrlController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MemoryController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\CommentController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/login/status', [AuthController::class, 'loginStatus']);


    Route::controller()->middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::controller(MemoryController::class)->group(function () {
            Route::get('/memories', 'getAllMemories');
            Route::get('/memories/title/{title}', 'show');
            Route::get('/gabriella/memories', 'getGabriellasMemories');
            Route::get('/pablo/memories', 'getPablosMemories');
            Route::get('/brunnis/memories', 'getBrunnisMemories');
        });

        Route::controller(SearchController::class)->group(function () {
            Route::get('/search/category/{category}', 'searchByCategory');
            Route::get('/search/date/{date}', 'searchByDate');
        });

        Route::controller(CommentController::class)->group(function () {
            Route::post('/memories/{title}/comment', 'create');
            Route::patch('/memories/{title}/comment/{id}', 'update');
            Route::delete('/memories/{title}/comment/{id}', 'delete');
        });

        Route::controller(FileController::class)->group(function () {
            Route::get('/file/{id}', 'show')->whereNumber('id');
        });

        Route::controller(UrlController::class)->group(function () {
            Route::get('/url/{id}', 'show')->whereNumber('id');
            Route::delete('/url/{id}', 'delete')->whereNumber('id');
            Route::patch('/url/{id}', 'update')->whereNumber('id');
        });

        // ADMIN & profile owners
        Route::controller(UserController::class)->group(function () {
            // Only 'admin' (set manually on DB) can see the fans list
            Route::get('/fans', 'index');
            Route::post('/approve-user/{userId}', 'approveUser');

            Route::controller(MemoryController::class)->group(function () {
                Route::post('/memory/create', 'createWithFile');
                Route::patch('/memories/{id}', 'update');
                Route::delete('/memories/{title}', 'delete');
                Route::get('/categories', 'getCategories');
            });

            Route::controller(FileController::class)->group(function () {
                Route::delete('/file/{id}', 'delete')->whereNumber('id');
                Route::post('/file/{id}', 'update')->whereNumber('id');
            });

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
