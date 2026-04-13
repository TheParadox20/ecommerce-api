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
            'logo' => 'nullable|image|max:2048',
            'description' => 'nullable|string',
            'color_hex' => 'nullable|string|max:7',
            'is_active' => 'nullable|boolean',
            'facebook_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
        ]);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $name = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
            $file->move(public_path('storage/brands'), $name);
            $validated['logo'] = url('storage/brands/' . $name);
        }

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
            'logo' => 'nullable|image|max:2048',
            'description' => 'nullable|string',
            'color_hex' => 'nullable|string|max:7',
            'is_active' => 'nullable|boolean',
            'facebook_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
        ]);

        if ($request->hasFile('logo')) {
            // Delete old logo if it exists
            if ($brand->logo) {
                try {
                    $oldPath = str_replace(url(''), public_path(), $brand->logo);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to delete old brand logo: ' . $e->getMessage());
                }
            }

            $file = $request->file('logo');
            $name = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
            $file->move(public_path('storage/brands'), $name);
            $validated['logo'] = url('storage/brands/' . $name);
        }

        $brand->update($validated);
        return response()->json([
            'success' => true,
            'brand' => $brand->load(['categories'])
        ]);
    }

    public function destroy($id)
    {
        try {
            $brand = Brand::findOrFail($id);
            
            // Delete logo if it exists and is local
            if ($brand->logo && str_contains($brand->logo, url('storage/brands'))) {
                try {
                    $oldPath = str_replace(url(''), public_path(), $brand->logo);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to unlink brand logo on destroy: ' . $e->getMessage());
                }
            }

            $brand->delete();
            return response()->json(['success' => true, 'message' => 'Brand deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
} 