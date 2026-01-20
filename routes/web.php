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
| Note: posts/create must be defined before posts/{post} to avoid route conflict.
|
*/

// Public index route
Route::get('posts', [PostController::class, 'index'])->name('posts.index');

// Authenticated routes (defined before posts/{post} to prevent wildcard matching "create")
Route::middleware('auth')->group(function () {
    Route::get('posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::match(['put', 'patch'], 'posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
});

// Public show route (must be after posts/create)
Route::get('posts/{post}', [PostController::class, 'show'])->name('posts.show');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
