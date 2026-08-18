<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\Recipe;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Recipe::query();
            
            // Public only sees published
            if (!$request->user() || !$request->user()->isAdmin()) {
                $query->where('status', 'published');
            }
            
            // Search functionality
            if ($request->has('search') && $request->search) {
                $query->where('title', 'like', '%' . $request->search . '%')
                      ->orWhere('content', 'like', '%' . $request->search . '%');
            }
            
            // Product filter
            $productId = $request->get('product_id') ?: $request->get('product');
            if ($productId) {
                $query->whereHas('products', function($q) use ($productId) {
                    $q->where('products.id', $productId);
                });
            }
            
            // Category filter
            if ($request->has('category') && $request->category) {
                $query->byCategory($request->category);
            }
            
            // Difficulty filter
            if ($request->has('difficulty') && $request->difficulty) {
                $query->where('difficulty', $request->difficulty);
            }
            
            $recipes = $query->with(['products' => function($q) {
                $q->with(['productVariations', 'productImages']);
            }])->orderBy('created_at', 'desc')->paginate(12);
            
            return response()->json([
                'success' => true,
                'data' => $recipes
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($slug)
    {
        try {
            $recipe = Recipe::with(['products' => function($q) {
                $q->with(['productVariations', 'productImages']);
            }])->where('slug', $slug)->first();
            
            if (!$recipe) {
                // Try finding by ID as fallback for admin
                $recipe = Recipe::with(['products' => function($q) {
                    $q->with(['productVariations', 'productImages']);
                }])->find($slug);
            }

            if (!$recipe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recipe not found'
                ], 404);
            }
            
            // Increment views
            $recipe->increment('views');
            
            // Get related recipes
            $relatedRecipes = Recipe::where('category', $recipe->category)
                                   ->where('id', '!=', $recipe->id)
                                   ->where('status', 'published')
                                   ->limit(6)
                                   ->get();
            
            return response()->json([
                'success' => true,
                'recipe' => $recipe,
                'related' => $relatedRecipes
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Decode ingredients and instructions if they come as strings (from FormData)
            if (is_string($request->ingredients)) {
                $request->merge(['ingredients' => json_decode($request->ingredients, true)]);
            }
            if (is_string($request->instructions)) {
                $request->merge(['instructions' => json_decode($request->instructions, true)]);
            }

            if ($request->has('noindex')) {
                $val = $request->noindex;
                $request->merge(['noindex' => ($val === 'null' || $val === 'undefined' || $val === '' || is_null($val)) ? false : filter_var($val, FILTER_VALIDATE_BOOLEAN)]);
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'slug' => 'required|string|unique:recipes,slug',
                'content' => 'nullable|string',
                'ingredients' => 'required|array|min:1',
                'instructions' => 'required|array|min:1',
                'cooking_time' => 'required|integer|min:1',
                'servings' => 'required|integer|min:1',
                'difficulty' => 'required|in:easy,medium,hard',
                'category' => 'required|string',
                'status' => 'required|in:draft,published',
                'image' => 'nullable', // Can be string or file
                'video_url' => 'nullable|string',
                'product_ids' => 'nullable|array',
                'product_ids.*' => 'exists:products,id',
                // SEO fields
                'seo_title'       => 'nullable|string|max:70',
                'seo_description' => 'nullable|string|max:165',
                'seo_keywords'    => 'nullable|string|max:500',
                'canonical_url'   => 'nullable|string|max:500',
                'noindex'         => 'nullable|boolean',
            ]);

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $destinationPath = public_path("storage/recipes");
                $extension = $file->getClientOriginalExtension();
                $name = time() . '_' . str_replace(' ', '_', $validated['slug']) . '.' . $extension;
                $file->move($destinationPath, $name);
                $validated['image'] = url("storage/recipes/" . $name);
            }

            $recipe = Recipe::create($validated);

            if ($request->has('product_ids')) {
                $recipe->products()->sync($request->product_ids);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Recipe created successfully',
                'data' => $recipe->load('products')
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $recipe = Recipe::findOrFail($id);

            // Decode ingredients and instructions if they come as strings (from FormData)
            if (is_string($request->ingredients)) {
                $request->merge(['ingredients' => json_decode($request->ingredients, true)]);
            }
            if (is_string($request->instructions)) {
                $request->merge(['instructions' => json_decode($request->instructions, true)]);
            }

            if ($request->has('noindex')) {
                $val = $request->noindex;
                $request->merge(['noindex' => ($val === 'null' || $val === 'undefined' || $val === '' || is_null($val)) ? false : filter_var($val, FILTER_VALIDATE_BOOLEAN)]);
            }
            
            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'slug' => 'sometimes|required|string|unique:recipes,slug,' . $id,
                'content' => 'nullable|string',
                'ingredients' => 'sometimes|required|array|min:1',
                'instructions' => 'sometimes|required|array|min:1',
                'cooking_time' => 'sometimes|required|integer|min:1',
                'servings' => 'sometimes|required|integer|min:1',
                'difficulty' => 'sometimes|required|in:easy,medium,hard',
                'category' => 'sometimes|required|string',
                'status' => 'sometimes|required|in:draft,published',
                'image' => 'nullable', // can be string or file
                'video_url' => 'nullable|string',
                'product_ids' => 'nullable|array',
                'product_ids.*' => 'exists:products,id',
                // SEO fields
                'seo_title'       => 'nullable|string|max:70',
                'seo_description' => 'nullable|string|max:165',
                'seo_keywords'    => 'nullable|string|max:500',
                'canonical_url'   => 'nullable|string|max:500',
                'noindex'         => 'nullable|boolean',
            ]);

            if ($request->hasFile('image')) {
                // Delete old image if it exists and is local
                if ($recipe->image && str_contains($recipe->image, url('storage/recipes'))) {
                    $oldPath = public_path(str_replace(url('/'), '', $recipe->image));
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $file = $request->file('image');
                $destinationPath = public_path("storage/recipes");
                $extension = $file->getClientOriginalExtension();
                $slug = $validated['slug'] ?? $recipe->slug;
                $name = time() . '_' . str_replace(' ', '_', $slug) . '.' . $extension;
                $file->move($destinationPath, $name);
                $validated['image'] = url("storage/recipes/" . $name);
            }

            $recipe->update($validated);

            if ($request->has('product_ids')) {
                $recipe->products()->sync($request->product_ids);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Recipe updated successfully',
                'data' => $recipe->load('products')
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy($id)
    {
        try {
            $recipe = Recipe::findOrFail($id);
            
            // Delete image if it exists and is local
            if ($recipe->image && str_contains($recipe->image, url('storage/recipes'))) {
                $oldPath = public_path(str_replace(url('/'), '', $recipe->image));
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $recipe->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Recipe deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
} 