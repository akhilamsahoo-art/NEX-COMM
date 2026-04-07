<?php

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use App\Models\Category;
class CategoryController extends Controller
{
    public function index()
    {
        $categories = \App\Models\Category::all();
    
        return response()->json([
            'status' => true,
            'data' => $categories
        ]);
    }
    public function store(Request $request)
{
    // ✅ Validate input
    $request->validate([
        'name' => 'required|string|max:255'
    ]);

    // ✅ Create category
    $category = \App\Models\Category::create([
        'name' => $request->name
    ]);

    // ✅ Return response
    return response()->json([
        'status' => true,
        'message' => 'Category created successfully',
        'data' => $category
    ]);
}
public function update(Request $request, $id)
{
    $category = \App\Models\Category::findOrFail($id);

    $category->update([
        'name' => $request->name
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Category updated'
    ]);
}
public function destroy($id)
{
    $category = \App\Models\Category::findOrFail($id);
    $category->delete();

    return response()->json([
        'status' => true,
        'message' => 'Category deleted'
    ]);
}
}
