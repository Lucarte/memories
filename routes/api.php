<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UrlController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ReplyController;
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
            Route::post('/memory', 'createWithFile');
            Route::patch('/memory/{title}', 'update');
            Route::delete('/memory/{title}', 'delete');
        });

        Route::controller(CommentController::class)->group(function () {
            Route::post('/memory/{title}/comment', 'create');
            Route::patch('/memory/{title}/comment/{id}', 'update');
            Route::delete('/memory/{title}/comment/{id}', 'delete');
        });

        Route::controller(ReplyController::class)->group(function () {
            Route::post('/comment/{id}/reply', 'create');
            Route::patch('/comment/{id}/reply/{replyId}', 'update');
            Route::delete('/comment/{id}/reply/{replyId}', 'delete');
        });

        Route::controller(FileController::class)->group(function () {
            Route::get('/file/{id}', 'show')->whereNumber('id');
            Route::delete('/file/{id}', 'delete')->whereNumber('id');
            // Route::patch('/file/{id}', 'update')->whereNumber('id'); // PATCH not working
            Route::post('/file/{id}', 'update')->whereNumber('id');
        });

        Route::controller(UrlController::class)->group(function () {
            Route::get('/url/{id}', 'show')->whereNumber('id');
            Route::delete('/url/{id}', 'delete')->whereNumber('id');
            Route::patch('/url/{id}', 'update')->whereNumber('id');
        });

        // Route::controller(SearchController::class)->group(function () {
        //     Route::get('/search/{category}/{keyword}', 'CategoryKeywordIndex');
        //     Route::get('/search/{category}', 'CategoryOnlyIndex');
        //     Route::get('/searchTitle/{title}', 'TitleIndex');
        //     Route::get('/searchDate/{date}', 'DateIndex');
        // });

        // ADMIN & profile owners
        Route::controller(UserController::class)->group(function () {
            // Only 'admin' (set manually on DB) can see the fans list
            Route::get('/fans', 'index');

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
