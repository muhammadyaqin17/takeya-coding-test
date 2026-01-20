<?php

use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Post Routes
|--------------------------------------------------------------------------
|
| RESTful routes for the Post model.
| Using resource controller pattern for Laravel best practices.
|
*/

// Public routes - anyone can view published posts
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');

// Authenticated routes - must be before /posts/{post} to prevent route conflict
Route::middleware('auth')->group(function () {
    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::patch('/posts/{post}', [PostController::class, 'update']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
});

// Public show route - after authenticated routes to prevent {post} matching "create"
Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
