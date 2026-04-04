<?php

namespace App\Http\Controllers;

use App\Services\AiFoundationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Generate a professional product description using the AI Foundation Layer.
     * * @param Request $request
     * @param AiFoundationService $ai
     * @return JsonResponse
     */
    public function generateDescription(Request $request, AiFoundationService $ai): JsonResponse
    {
        // 1. Validation: Ensure the input is clean and doesn't exceed AI limits.
        // This prevents "Prompt Injection" and saves your API from processing 1GB of text.
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'features' => 'required|string|max:1000',
        ]);

        // 2. AI Generation: Pass the validated user input to your service.
        // The service handles the prompt mapping, API call, and DB logging.
        $description = $ai->generate('product_generator', [
            'name'     => $validated['name'],
            'features' => $validated['features']
        ]);

        // 3. Response: Return a structured JSON response.
        return response()->json([
            'status' => 'success',
            'data' => [
                'name' => $validated['name'],
                'description' => $description,
            ],
            'meta' => [
                'provider' => 'Gemini 1.5 Flash',
                'generated_at' => now()->toDateTimeString(),
            ]
        ], 200);
    }

    public function indexByStore($slug)
{
    return \App\Models\Product::whereHas('tenant', function ($q) use ($slug) {
        $q->where('slug', $slug);
    })->get();
}
public function indexByStore(Request $request)
{
    $tenant = $request->tenant;

    return Product::where('tenant_id', $tenant->id)->get();
}

public function showByStore($slug, $id)
{
    return \App\Models\Product::whereHas('tenant', function ($q) use ($slug) {
        $q->where('slug', $slug);
    })->where('id', $id)->firstOrFail();
}
}