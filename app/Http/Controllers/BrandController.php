<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index()
    {
        return Brand::with(['categories'])
            ->withCount('products')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();
    }

    public function store(Request $request)
    {
        if ($request->facebook_url === '') $request->merge(['facebook_url' => null]);
        if ($request->instagram_url === '') $request->merge(['instagram_url' => null]);

        $validated = $request->validate([
            'name' => 'required|string|unique:brands,name',
            'logo' => 'nullable|image|max:2048',
            'description' => 'nullable|string',
            'color_hex' => 'nullable|string|max:7',
            'is_active' => 'nullable|boolean',
            'facebook_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'min_order_amount' => 'nullable|numeric',
            'max_order_amount' => 'nullable|numeric',
            'tracking_snippet' => 'nullable|string',
            'purchase_snippet' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'slug' => 'nullable|string|unique:brands,slug',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
            // Ensure unique slug if auto-generated
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (Brand::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

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
        $brand = Brand::with(['categories'])->withCount('products')->findOrFail($id);
        return response()->json($brand);
    }

    public function update(Request $request, $id)
    {
        if ($request->facebook_url === '') $request->merge(['facebook_url' => null]);
        if ($request->instagram_url === '') $request->merge(['instagram_url' => null]);

        $brand = Brand::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|unique:brands,name,' . $id,
            'logo' => 'nullable|image|max:2048',
            'description' => 'nullable|string',
            'color_hex' => 'nullable|string|max:7',
            'is_active' => 'nullable|boolean',
            'facebook_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'min_order_amount' => 'nullable|numeric',
            'max_order_amount' => 'nullable|numeric',
            'tracking_snippet' => 'nullable|string',
            'purchase_snippet' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'slug' => 'nullable|string|unique:brands,slug,' . $id,
        ]);

        if (empty($validated['slug']) && isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
            // Ensure unique slug if auto-generated
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (Brand::where('slug', $validated['slug'])->where('id', '!=', $id)->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

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
            'brand' => $brand->load(['categories'])->loadCount('products')
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