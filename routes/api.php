<?php

use Illuminate\Support\Facades\Route;
use App\Models\Post;

Route::get('/posts', function () {
    return Post::all();
});