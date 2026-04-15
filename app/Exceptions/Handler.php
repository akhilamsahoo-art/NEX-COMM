<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = ['current_password', 'password', 'password_confirmation'];

    public function register()
    {
        $this->reportable(function (Throwable $e) {});
    }

    // protected function unauthenticated($request, AuthenticationException $exception)
    // {
    //     if ($request->expectsJson()) {
    //         return response()->json(['message' => 'Unauthenticated.'], 401);
    //     }

    //     return redirect()->guest(route('login'));
    // }

    protected function unauthenticated($request, AuthenticationException $exception)
{
    // Fix: If it's an API request, return JSON. Don't redirect.
    if ($request->expectsJson() || $request->is('api/*')) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    // Fix: Redirect based on the URL path
    if ($request->is('admin*')) {
        // return redirect()->guest(route('filament.auth.login'));
        return redirect()->guest('/admin/login');
    }

    return redirect()->guest(route('login'));
}
}