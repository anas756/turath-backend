<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\emailConfirmation;
use App\Http\Controllers\UserController;
use App\Http\Middleware\JwtAuthMiddleware;
use Illuminate\Support\Facades\Route;
use App\Models\Post;



Route::prefix('/')->group(function (){
//  user routes
//  without auth 
Route::prefix('users')->group(function ()  {
    Route::get('/show/All' , [UserController::class , 'index'])->middleware([JwtAuthMiddleware::class]);
    Route::post('/store' , [UserController::class , 'store']);
    Route::get('/email-confirm/{token}' , [emailConfirmation::class , 'confirmingEmail']);
    Route::put('/update/{user}' , [UserController::class , 'update'])->middleware([JwtAuthMiddleware::class]);
    Route::delete('/destroy/{user}' , [UserController::class , 'destroy'])->middleware([JwtAuthMiddleware::class]);

});
// auth routes
Route::post('login', [AuthController::class, 'login']);
Route::post('logout' , [AuthController::class , 'logout'])->middleware([JwtAuthMiddleware::class]);
Route::post('send-acount-confirmation' , [AuthController::class , 'manualSendEmailValidation']);

});

