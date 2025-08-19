<?php

namespace App\Http\Controllers;

use App\Models\Description;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DescriptionController extends Controller
{
    public function index()
    {
        // List all descriptions with their associated products
        return Description::with('product')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'description' => 'required|string',
        ]);

        $description = Description::create($validated);
        return response()->json(['success' => true, 'id' => $description->id], 201);
    }

    public function show(Request $request)
    {
        $description = Description::with('product.productImages')
            ->whereHas('product', function($query) use ($request) {
                $query->where('name', $request->product);
            })
            ->first();
        return response()->json($description);
    }

    public function update(Request $request, $id)
    {
        $description = Description::findOrFail($id);
        
        $validated = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'description' => 'sometimes|string',
        ]);

        $description->update($validated);
        return response()->json($description->load('product'));
    }

    public function destroy($id)
    {
        $description = Description::findOrFail($id);
        $description->delete();
        return response()->json(['message' => 'Description deleted']);
    }
} 