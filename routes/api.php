<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookContentController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\emailConfirmation;
use App\Http\Controllers\UserController;
use App\Http\Middleware\checkAppTokenSecret;
use App\Http\Middleware\JwtAuthMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
// protected by secret key

// Route::middleware([checkAppTokenSecret::class])->group(function() {




// auth 
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [UserController::class, 'store']);
// categorie
Route::apiResource('categories', CategorieController::class)
    ->only(['show', 'index']);
// books
Route::apiResource('book', BookController::class)->only(['show', 'index']);
// search book using word 
Route::get('search/library', [BookContentController::class, 'searchLibrer']);

// search inside book and get book content
Route::prefix('books/{book_id}')->group(function () {
    Route::get('pages', [BookContentController::class, 'getContentByBook']);
    Route::get('search', [BookContentController::class, 'searchInsideBook']);
});

// auth
Route::prefix('auth')->group(function () {
    // acount confirmation
    Route::get('email-confirm/{email}', [emailConfirmation::class, 'confirmingEmail']);
    Route::post('resend-confirmation', [AuthController::class, 'manualSendEmailValidation']);
    Route::get('email-verified/{email}', [emailConfirmation::class, 'checkVerified']);
    // password reset
    Route::post('forgot-password', [AuthController::class, 'sendResetPassToken']);
    Route::get('verify-reset-token/{email}', [emailConfirmation::class, 'verifyResetToken']);
    Route::post('reset-password', [UserController::class, 'updatePassword']);
});


// protected by jwt
Route::middleware([JwtAuthMiddleware::class])->group(function () {

    // auth
    Route::post('logout', [AuthController::class, 'logout']);

    // user
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::put('{user}', [UserController::class, 'update']);
        Route::delete('{user}', [UserController::class, 'destroy']);
    });
    // categorie
    Route::apiResource('categories', CategorieController::class)->except(['show', 'index']);
    Route::apiResource('book', BookController::class)->except(['show', 'index']);
});
    



// });