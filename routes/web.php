<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Homepage
Route::get('/', function () {
    return view('welcome');
});

// ✅ Global Login (MAIN ENTRY POINT)
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

// ✅ Logout
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Optional test route
Route::get('/test', function () {
    return "Laravel is working 🚀";
});

// Avatar debug route
Route::get('/test-avatar', function () {
    $user = auth()->user();

    return [
        'database_value' => $user->avatar_url,
        'generated_url' => Storage::disk('public')->url($user->avatar_url),
        'exists_on_disk' => Storage::disk('public')->exists($user->avatar_url),
        'app_url_from_env' => config('app.url'),
    ];
});