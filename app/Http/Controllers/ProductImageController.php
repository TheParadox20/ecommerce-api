<?php

namespace App\Http\Controllers;

use App\Models\ProductImage;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    public function index()
    {
        return ProductImage::with(['product', 'productVariation'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variation_id' => 'nullable|exists:product_variations,id',
            'url' => 'required|string',
            'is_primary' => 'nullable|boolean',
        ]);
        $image = ProductImage::create($validated);
        return response()->json($image->load(['product', 'productVariation']), 201);
    }

    public function show($id)
    {
        $image = ProductImage::with(['product', 'productVariation'])->findOrFail($id);
        return response()->json($image);
    }

    public function update(Request $request, $id)
    {
        $image = ProductImage::findOrFail($id);
        $validated = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'product_variation_id' => 'nullable|exists:product_variations,id',
            'url' => 'sometimes|string',
            'is_primary' => 'nullable|boolean',
        ]);
        $image->update($validated);
        return response()->json($image->load(['product', 'productVariation']));
    }

    public function destroy($id)
    {
        $image = ProductImage::findOrFail($id);
        $image->delete();
        return response()->json(['message' => 'Product image deleted']);
    }
}
