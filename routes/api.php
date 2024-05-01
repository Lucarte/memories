<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FanController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\MemoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\RegisterController;

// All routes that deal with registration or login, or that need authentification will have the prefix 'auth'
Route::prefix('auth')->group(function () {
    // Public Endpoints
    // Route::get('/register', [RegisterController::class, 'index']); // Do I even need this route?
    // Route::get('/login', [LoginController::class, 'index']); // Do I even need this route?
    // Route::post('/register', RegisterController::class);
    // Route::post('/login', LoginController::class);

    // Secure Endpoints - needing authentication
    Route::controller()->middleware('auth:sanctum')->group(function () {
        // Route::post('/logout', LogoutController::class);

        Route::controller(MemoryController::class)->group(function () {
            // // => So ok?
            Route::get('/memories', 'show');
            Route::get('/memories/{kid}', 'show');
            Route::get('/memory', 'form'); // Do I even need this route?
            Route::post('/memory', 'create');
            Route::patch('/memory/{title}', 'update');
            Route::delete('/memory/{title}', 'delete');

            //  // => Oder brauche ich explizit patch oder delete ?
            // Route::get('/memory/form', 'form'); // Do I even need this route?
            // Route::post('/memory/create', 'create');
            // Route::patch('/memory/update/{title}', 'update');
            // Route::delete('/memory/delete/{title}', 'delete');
        });

        Route::controller(CommentController::class)->group(function () {
            Route::get('/memory/{title}/comment', 'form'); // Do I even need this route?
            Route::get('/memory/{title}/comments', 'show'); // If the '/memory/{title}' endpoint shows all comments by default I would not need this endpoint, correct? I could just work with css to make the comments hide, right?
            Route::post('/memory/{title}/comment', 'create');
            Route::patch('/memory/{title}/comment/{id}', 'update');
            Route::delete('/memory/{title}/comment/{id}', 'delete');
        });

        // Route::controller(SearchController::class)->group(function () {
        //     Route::get('/search/{category}', 'show');
        //     Route::get('/search/{title}', 'show');
        //     Route::get('/search/{date}', 'show');
        // });

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
