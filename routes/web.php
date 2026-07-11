<?php

use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\DigestPreferenceController;
use App\Http\Controllers\PlatformPreferenceController;
use App\Http\Controllers\PostDismissalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RssController;
use App\Http\Controllers\TrendingController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [TrendingController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/feed/{token}.rss', [RssController::class, 'show'])->name('feed.rss');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::patch('/platform-preferences', [PlatformPreferenceController::class, 'update'])->name('platform-preferences.update');
    Route::post('/posts/{post}/bookmark', [BookmarkController::class, 'store'])->name('bookmarks.store');
    Route::delete('/posts/{post}/bookmark', [BookmarkController::class, 'destroy'])->name('bookmarks.destroy');
    Route::post('/posts/{post}/dismiss', [PostDismissalController::class, 'store'])->name('dismissals.store');
    Route::delete('/posts/{post}/dismiss', [PostDismissalController::class, 'destroy'])->name('dismissals.destroy');
    Route::patch('/digest-preference', [DigestPreferenceController::class, 'update'])->name('digest-preference.update');
});

require __DIR__.'/auth.php';
