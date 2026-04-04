<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\Request;

// ✅ ADDED
use App\Http\Requests\StoreProductRequest;
use App\Helpers\ApiResponse;

class AdminProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    // ✅ UPDATED (uses FormRequest + ApiResponse)
    public function store(Request $request)
    {
        $data = $request->validated(); // ✅ validation added

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image'] = $path;
        }

        $product = $this->productService->createProduct($data);

        return ApiResponse::success($product, 'Product created successfully');
    }

    // ✅ UPDATED (added response wrapper)
    public function update(Request $request, $id)
    {
        $product = $this->productService->updateProduct($id, $request->all());

        return ApiResponse::success($product, 'Product updated successfully');
    }

    // ✅ UPDATED (added response wrapper)
    public function destroy($id)
    {
        $this->productService->deleteProduct($id);

        return ApiResponse::success(null, 'Product deleted successfully');
    }
}