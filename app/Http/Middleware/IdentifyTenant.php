<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;

class IdentifyTenant
{
    public function handle($request, Closure $next)
    {
        $slug = $request->route('slug');

        if ($slug) {
            $tenant = Tenant::where('slug', $slug)
                ->where('is_active', true)
                ->firstOrFail();

            // Attach tenant to request
            $request->merge(['tenant' => $tenant]);
        }

        return $next($request);
    }
}