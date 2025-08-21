<?php

namespace App\Http\Controllers;

use App\Models\ProductFAQ;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductFAQController extends Controller
{
    public function index(Request $request)
    {
        $product = $request->product;
        // List all product FAQs with product relationship
        return ProductFAQ::with('product')->whereHas('product', function($query) use ($product) {
            $query->where('name', $product);
        })->get();
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'question' => 'required|string',
                'answer' => 'required|string',
            ]);
            
            $productFAQ = ProductFAQ::create($validated);
            return response()->json(['success' => true, 'id' => $productFAQ->id], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->validator->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($product)
    {
        $productFAQ = ProductFAQ::with('product')->whereHas('product', function($query) use ($product) {
            $query->where('name', $product);
        })->get();
        return response()->json($productFAQ);
    }

    public function update(Request $request, $id)
    {
        try {
            $productFAQ = ProductFAQ::findOrFail($id);
            
            $validated = $request->validate([
                'product_id' => 'sometimes|exists:products,id',
                'question' => 'sometimes|string',
                'answer' => 'sometimes|string',
            ]);
            
            $productFAQ->update($validated);
            return response()->json(['success' => true, 'data' => $productFAQ->load('product')]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->validator->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $productFAQ = ProductFAQ::findOrFail($id);
            $productFAQ->delete();
            return response()->json(['success' => true, 'message' => 'Product FAQ deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
} 