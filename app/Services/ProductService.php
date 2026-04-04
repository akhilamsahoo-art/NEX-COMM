<?php
namespace App\Services;

use App\Models\Product;

class ProductService
{
    public function getAllProducts($request)
    {
        $query = Product::with('category');

        // 🔍 Filter by category
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // 🔍 Search
        if ($request->search) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        return $query->paginate(10);
    }

    public function getProductById($id)
    {
        return Product::with('category')->findOrFail($id);
    }

    public function createProduct(array $data)
    {
        $user = auth()->user();
    
        // ✅ Role check
        if (!in_array($user->role, ['super_admin', 'admin', 'manager', 'seller'])) {
            abort(403, 'Unauthorized');
        }
    
        // ✅ Assign tenant automatically
        if ($user->role !== 'super_admin') {
            $data['tenant_id'] = $user->tenant_id;
        }
    
        return Product::create($data);
    }

    public function updateProduct($id, array $data)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['super_admin', 'admin', 'manager', 'seller'])) {
            throw new \Exception('Unauthorized');
        }

        $product = Product::findOrFail($id);
        $product->update($data);

        return $product;
    }

    public function deleteProduct($id)
    {
        $user = auth()->user();

        // 🔥 Only higher roles can delete
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            throw new \Exception('Unauthorized');
        }

        $product = Product::findOrFail($id);
        return $product->delete();
    }
}