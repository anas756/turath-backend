<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Models\Post;

Route::get('/posts', function () {
    return Post::all();
});

//  user routes
//  without auth 

Route::prefix('users')->group(function ()  {
    Route::get('/show/All' , [UserController::class , 'index']);
    Route::post('/store' , [UserController::class , 'store']);

});