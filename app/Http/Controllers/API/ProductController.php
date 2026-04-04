<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    // ✅ GET /products
    public function index(Request $request)
{
    $query = Product::query();

    // 🔍 Search
    if ($request->has('search')) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

    // 🏷️ Category filter (only if relation exists)
    if ($request->has('category')) {
        $query->whereHas('category', function ($q) use ($request) {
            $q->where('slug', $request->category);
        });
    }

    // 📄 Pagination
    $products = $query->paginate(10);

    return response()->json([
        'status' => true,
        'data' => $products
    ]);
}

    // ✅ GET /products/{id}
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
public function indexByStore($slug)
{
    $tenant = \App\Helpers\TenantHelper::getTenantBySlug($slug);

    return \App\Models\Product::where('tenant_id', $tenant->id)
        ->where('is_active', true)
        ->get();
}

public function showByStore($slug, $id)
{
    $tenant = \App\Helpers\TenantHelper::getTenantBySlug($slug);

    return \App\Models\Product::where('tenant_id', $tenant->id)
        ->where('id', $id)
        ->firstOrFail();
}
    // ✅ POST /products
    public function store(Request $request)
    {
        $product = Product::create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Product created',
            'data' => $product
        ]);
    }

    // ✅ PUT /products/{id}
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

    // ✅ DELETE /products/{id}
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