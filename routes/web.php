<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// 1. Comment out Breeze
// require __DIR__.'/auth.php'; 

// 2. Redirect /login to the actual Filament login path
// This fixes the 404 because it forces the browser to the real URL
Route::redirect('/login', '/admin/login')->name('login');