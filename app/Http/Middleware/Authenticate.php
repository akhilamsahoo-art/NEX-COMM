<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    // protected function redirectTo(Request $request): ?string
    // {
    //     return $request->expectsJson() ? null : route('login');
    // }

    protected function redirectTo($request)
{
    if (! $request->expectsJson()) {
        // If the user is trying to access Filament, send them to Filament login
        if ($request->is('admin*')) {
            return route('filament.auth.login');
        }

        // Standard user login (Breeze)
        return route('login');
    }
}
}