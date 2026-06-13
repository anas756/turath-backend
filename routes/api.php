<?php

use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentContentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\emailConfirmation;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\OpenLibrarySyncController;
use App\Http\Middleware\checkAppTokenSecret;
use App\Http\Middleware\JwtAuthMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Route::middleware([checkAppTokenSecret::class])->group(function () {

    // Auth
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [UserController::class, 'store']);

    // Categorie (Public)
    Route::apiResource('categories', CategorieController::class)
        ->only(['show', 'index']);

    // Documents (Public)
    Route::apiResource('library/docs', DocumentController::class)
        ->only(['show', 'index']);

    // Search global (using word)
    Route::get('search/library', [DocumentContentController::class, 'searchLibrary']);

    // Search inside docs and get docs content
    Route::prefix('/library/docs/{document_id}')->group(function () {
        Route::get('pages', [DocumentContentController::class, 'getContentByDocument']);
        Route::get('search', [DocumentContentController::class, 'searchInsideDocument']);
    });
// routes/api.php
    Route::get('/landing/preview', [LandingController::class, 'preview']);    // Auth flows (Public)
    Route::prefix('auth')->group(function () {
        Route::get('email-confirm/{email}', [emailConfirmation::class, 'confirmingEmail']);
        Route::post('resend-confirmation', [AuthController::class, 'manualSendEmailValidation']);
        Route::get('email-verified/{email}', [emailConfirmation::class, 'checkVerified']);
        Route::post('forgot-password', [AuthController::class, 'sendResetPassToken']);
        Route::get('verify-reset-token/{email}', [emailConfirmation::class, 'verifyResetToken']);
        Route::post('reset-password', [UserController::class, 'updatePassword']);
    });

    // Protected by JWT (All routes here require authentication)
    Route::middleware([JwtAuthMiddleware::class])->group(function () {
        // Auth
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('/auth/profile', [AuthController::class, 'getProfile']);

        // User CRUD
        Route::apiResource('users', UserController::class)->except('store');

        // Categorie CRUD
        Route::apiResource('categories', CategorieController::class)->except(['show', 'index']);

        // Document CRUD (Protected)
        Route::apiResource('library/docs', DocumentController::class)->except(['show', 'index']);

        // All media operations require JWT token
        Route::apiResource('media', MediaController::class)->parameters([
            'media' => 'media' 
        ]);;

        // Additional media routes
        Route::post('media/bulk-delete', [MediaController::class, 'bulkDelete']);
        Route::put('media/{media}/status', [MediaController::class, 'updateStatus']);
        // dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

        // Open Library background import
        Route::post('open-library/sync', OpenLibrarySyncController::class);

        Route::prefix('favorites')->group(function () {
            Route::get('/', [FavoriteController::class, 'index']);  // all data + counts
            Route::post('/document', [FavoriteController::class, 'storeDocument']);  // add document
            Route::post('/media', [FavoriteController::class, 'storeMedia']);     // add media
            Route::delete('/{type}/{favorable_id}', [FavoriteController::class, 'destroy']);
        });
    });
// });
