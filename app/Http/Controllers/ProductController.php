<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function index()
    {
        // List all products with variations, images, and category
        return Product::with(['productVariations.attributeValues.attribute', 'productImages', 'category'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:products,name',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'about' => 'nullable|string',
            'price' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
        ]);
        $product = Product::create($validated);
        return response()->json($product->load(['productVariations.attributeValues.attribute', 'productImages', 'category']), 201);
    }

    public function show($id)
    {
        $product = Product::with(['productVariations.attributeValues.attribute', 'productImages', 'category'])->findOrFail($id);
        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|unique:products,name,' . $id,
            'category_id' => 'sometimes|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'about' => 'nullable|string',
            'price' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
        ]);
        $product->update($validated);
        return response()->json($product->load(['productVariations.attributeValues.attribute', 'productImages', 'category']));
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['message' => 'Product deleted']);
    }
}
