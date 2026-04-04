<?php

namespace App\Helpers;

use App\Models\Tenant;

class TenantHelper
{
    public static function getTenantBySlug($slug)
    {
        return Tenant::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }
}


