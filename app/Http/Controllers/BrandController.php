<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        return Brand::with(['categories'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:brands,name',
        ]);

        $brand = Brand::create($validated);
        return response()->json(['success'=>true, 'id'=>$brand->id], 201);
    }

    public function show($id)
    {
        $brand = Brand::with(['categories'])->findOrFail($id);
        return response()->json($brand);
    }

    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|unique:brands,name,' . $id,
        ]);

        $brand->update($validated);
        return response()->json($brand->load(['categories']));
    }

    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);
        $brand->delete();
        return response()->json(['message' => 'Brand deleted']);
    }
} 