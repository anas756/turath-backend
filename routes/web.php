<?php

use Illuminate\Support\Facades\Route;
use App\Models\Post;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    Post::create([
        'title' => 'Hello MongoDB',
        'content' => 'It works!'
    ]);

    return Post::all();
});