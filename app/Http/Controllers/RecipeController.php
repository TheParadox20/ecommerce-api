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
            if ($request->has('product') && $request->product) {
                $query->whereHas('products', function($q) use ($request) {
                    $q->where('products.id', $request->product);
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
            
            $recipes = $query->with('products')->orderBy('created_at', 'desc')->paginate(12);
            
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
            $recipe = Recipe::with('products')->where('slug', $slug)->first();
            
            if (!$recipe) {
                // Try finding by ID as fallback for admin
                $recipe = Recipe::with('products')->find($slug);
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
                'image' => 'nullable|string',
                'video_url' => 'nullable|string',
                'product_ids' => 'nullable|array',
                'product_ids.*' => 'exists:products,id'
            ]);

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
                'image' => 'nullable|string',
                'video_url' => 'nullable|string',
                'product_ids' => 'nullable|array',
                'product_ids.*' => 'exists:products,id'
            ]);

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