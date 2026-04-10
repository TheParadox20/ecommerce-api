<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return Category::withTrashed()->with(['brand.products'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'parent_id' => 'nullable|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
        ]);
        
        $category = Category::create($validated);
        return response()->json(['success'=>true, 'id'=>$category->id], 201);
    }

    public function show($id)
    {
        $category = Category::with(['parent', 'children', 'products'])->findOrFail($id);
        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|unique:categories,name,' . $id,
            'parent_id' => 'nullable|exists:categories,id',
        ]);
        $category->update($validated);
        return response()->json($category->load(['parent', 'children', 'products']));
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        return response()->json(['success' => true, 'message' => 'Category moved to trash']);
    }

    public function restore($id)
    {
        $category = Category::withTrashed()->where('id', $id)->firstOrFail();
        $category->restore();
        return response()->json(['success' => true, 'message' => 'Category restored successfully']);
    }
}
