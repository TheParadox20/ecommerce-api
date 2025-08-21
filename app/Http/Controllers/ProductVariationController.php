<?php

namespace App\Http\Controllers;

use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductVariationController extends Controller
{
    public function index()
    {
        return ProductVariation::with(['attributeValues.attribute', 'productImages', 'product'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'attribute_name' => 'required|string',
            'attribute_value' => 'required|string',
            'discount' => 'nullable|numeric',
            'status' => 'nullable|string',
            'image' => 'nullable|string',
        ]);
        $sku = $validated['product_id'].'-'.Str::slug($validated['attribute_name']).'-'.Str::slug($validated['attribute_value']);
        $validated['sku'] = $sku;
        $variation = ProductVariation::create($validated);
        return response()->json([
            'success' => true,
            'id' => $variation->id
        ], 201);
    }

    public function show($id)
    {
        $variation = ProductVariation::with(['attributeValues.attribute', 'productImages', 'product'])->findOrFail($id);
        return response()->json($variation);
    }

    public function update(Request $request, $id)
    {
        $variation = ProductVariation::findOrFail($id);
        $validated = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'sku' => 'sometimes|string|unique:product_variations,sku,' . $id,
            'price' => 'sometimes|numeric',
            'stock' => 'sometimes|integer',
            'discount' => 'nullable|numeric',
            'status' => 'nullable|string',
            'image' => 'nullable|string',
        ]);
        $variation->update($validated);
        if ($request->has('attribute_value_ids')) {
            $variation->attributeValues()->sync($request->input('attribute_value_ids'));
        }
        return response()->json($variation->load(['attributeValues.attribute', 'productImages', 'product']));
    }

    public function destroy($id)
    {
        $variation = ProductVariation::findOrFail($id);
        $variation->delete();
        return response()->json(['message' => 'Product variation deleted']);
    }
}
