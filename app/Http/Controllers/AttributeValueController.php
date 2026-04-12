<?php

namespace App\Http\Controllers;

use App\Models\AttributeValue;
use Illuminate\Http\Request;

class AttributeValueController extends Controller
{
    public function index()
    {
        return AttributeValue::with(['attribute', 'productVariations'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'attribute_id' => 'required|exists:attributes,id',
            'value' => 'required|string',
        ]);
        $attributeValue = AttributeValue::create($validated);
        // Attach product variations if provided
        if ($request->has('product_variation_ids')) {
            $attributeValue->productVariations()->sync($request->input('product_variation_ids'));
        }
        return response()->json([
            'success' => true,
            'attribute_value' => $attributeValue->load(['attribute', 'productVariations'])
        ], 201);
    }

    public function show($id)
    {
        $attributeValue = AttributeValue::with(['attribute', 'productVariations'])->findOrFail($id);
        return response()->json($attributeValue);
    }

    public function update(Request $request, $id)
    {
        $attributeValue = AttributeValue::findOrFail($id);
        $validated = $request->validate([
            'attribute_id' => 'sometimes|exists:attributes,id',
            'value' => 'sometimes|string',
        ]);
        $attributeValue->update($validated);
        if ($request->has('product_variation_ids')) {
            $attributeValue->productVariations()->sync($request->input('product_variation_ids'));
        }
        return response()->json([
            'success' => true,
            'attribute_value' => $attributeValue->load(['attribute', 'productVariations'])
        ]);
    }

    public function destroy($id)
    {
        $attributeValue = AttributeValue::findOrFail($id);
        $attributeValue->delete();
        return response()->json(['success' => true, 'message' => 'Attribute value deleted']);
    }
}
