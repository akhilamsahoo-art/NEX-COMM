<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Services\AiFoundationService; 
use Illuminate\Http\JsonResponse;
use App\Helpers\TenantHelper; // Added for cleaner helper calls

class ProductController extends Controller
{
    /**
     * ✅ AI Description Generator
     * Logic: Uses Gemini AI to generate product descriptions based on name/features.
     */
    public function generateDescription(Request $request, AiFoundationService $ai): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'features' => 'required|string|max:1000',
        ]);

        $description = $ai->generate('product_generator', [
            'name'     => $validated['name'],
            'features' => $validated['features']
        ]);

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

    /**
     * ✅ GET /products
     * Logic: Standard listing with Search, Category Filter, and Pagination.
     */
    public function index(Request $request)
    {
        // $query = Product::query();
        $query = Product::withoutGlobalScopes();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        $products = $query->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $products
        ]);
    }

    /**
     * ✅ GET /products/{slug}
     * Logic: Fetch a single product by its slug.
     */
    public function show($slug)
    {
        $product = Product::where('slug', $slug)->first();

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $product
        ]);
    }

    /**
     * ✅ GET /store/{slug}/products
     * Logic: Fetch all products belonging to a specific Tenant/Store.
     */
    public function indexByStore($slug)
    {
        $tenant = TenantHelper::getTenantBySlug($slug);

        $products = Product::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $products
        ]);
    }

    /**
     * ✅ GET /store/{slug}/products/{id}
     * Logic: Fetch a specific product within a specific Tenant.
     */
    public function showByStore($slug, $id)
    {
        $tenant = TenantHelper::getTenantBySlug($slug);

        $product = Product::where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'data' => $product
        ]);
    }

    /**
     * ✅ POST /products
     * Logic: Create a new product.
     */
    public function store(Request $request)
    {
        $product = Product::create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Product created',
            'data' => $product
        ]);
    }

    /**
     * ✅ PUT /products/{id}
     * Logic: Update an existing product.
     */
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $product->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Product updated',
            'data' => $product
        ]);
    }

    /**
     * ✅ DELETE /products/{id}
     * Logic: Remove a product from the database.
     */
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $product->delete();

        return response()->json([
            'status' => true,
            'message' => 'Product deleted'
        ]);
    }
}