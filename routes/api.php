<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\emailConfirmation;
use App\Http\Controllers\UserController;
use App\Http\Middleware\JwtAuthMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// PUBLIC ROUTES
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [UserController::class, 'store']);
// AUTH ROUTES
Route::prefix('auth')->group(function () {
    // ACOUNT CONFIRMATION
    Route::get('email-confirm/{email}', [emailConfirmation::class, 'confirmingEmail']);
    Route::post('resend-confirmation', [AuthController::class, 'manualSendEmailValidation']);

    // PASSWORD RESET 
    Route::post('forgot-password', [AuthController::class, 'sendResetPassToken']);
    Route::get('verify-reset-token/{email}', [emailConfirmation::class, 'verifyResetToken']);
    Route::post('reset-password', [UserController::class, 'updatePassword']);
});


// PROTECTED ROUTS
Route::middleware([JwtAuthMiddleware::class])->group(function () {

    // AUTH
    Route::post('logout', [AuthController::class, 'logout']);

    // USER CRUD
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);      // GET /users (Liste tout)
        Route::put('{user}', [UserController::class, 'update']); // PUT /users/{id}
        Route::delete('{user}', [UserController::class, 'destroy']); // DELETE /users/{id}
    });
});
