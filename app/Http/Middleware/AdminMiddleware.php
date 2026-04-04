<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // ❌ Not logged in
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // 🔒 Allow only admin roles
        if (!in_array($user->role, ['super_admin', 'manager', 'seller'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}