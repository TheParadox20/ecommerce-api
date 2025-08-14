<?php

namespace App\Http\Controllers;

use App\Models\ProductVariation;
use Illuminate\Http\Request;

class ProductVariationController extends Controller
{
    public function index()
    {
        return ProductVariation::with(['attributeValues.attribute', 'productImages', 'product'])->get();
    }

    public function store(Request $request)
    {
        $data = $request->all();

        // If the request is a single object, wrap it in an array for uniformity
        $variationsData = isset($data[0]) ? $data : [$data];

        $createdVariations = [];

        foreach ($variationsData as $variationData) {
            $validated = validator($variationData, [
                'product_id' => 'required|exists:products,id',
                'sku' => 'required|string|unique:product_variations,sku',
                'price' => 'required|numeric',
                'stock' => 'required|integer',
                'discount' => 'nullable|numeric',
                'status' => 'nullable|string',
                'image' => 'nullable|string',
                'attribute_value_ids' => 'sometimes|array',
                'attribute_value_ids.*' => 'integer|exists:attribute_values,id',
            ])->validate();

            // Remove attribute_value_ids before creating the variation
            $attributeValueIds = $variationData['attribute_value_ids'] ?? [];
            unset($validated['attribute_value_ids']);

            $variation = ProductVariation::create($validated);

            // Attach attribute values if provided
            if (!empty($attributeValueIds)) {
                $variation->attributeValues()->sync($attributeValueIds);
            }

            $createdVariations[] = $variation->load(['attributeValues.attribute', 'productImages', 'product']);
        }

        return response()->json(['success'=>true], 201);
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
