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
            
            // Search functionality
            if ($request->has('search') && $request->search) {
                $query->where('title', 'like', '%' . $request->search . '%')
                      ->orWhere('content', 'like', '%' . $request->search . '%');
            }
            
            // Category filter
            if ($request->has('category') && $request->category) {
                $query->byCategory($request->category);
            }
            
            // Difficulty filter
            if ($request->has('difficulty') && $request->difficulty) {
                $query->where('difficulty', $request->difficulty);
            }
            
            // Featured recipes
            if ($request->has('featured') && $request->featured) {
                $query->featured();
            }
            
            $recipes = $query->orderBy('created_at', 'desc')->paginate(12);
            
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

    public function show(Request $request)
    {
        try {
            $recipe = Recipe::where('title', str_replace('-', ' ', $request->recipe))->first();
            
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
                                  ->limit(6)
                                  ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'recipe' => $recipe,
                    'related' => $relatedRecipes
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function popular()
    {
        try {
            $recipes = Recipe::popular()->limit(6)->get();
            
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

    public function featured()
    {
        try {
            $recipes = Recipe::featured()->limit(6)->get();
            
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

    public function categories()
    {
        try {
            $categories = Recipe::select('category')
                               ->distinct()
                               ->pluck('category');
            
            return response()->json([
                'success' => true,
                'data' => $categories
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
            $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'ingredients' => 'required|array',
                'instructions' => 'required|array',
                'cooking_time' => 'required|integer|min:1',
                'servings' => 'required|integer|min:1',
                'difficulty' => 'required|in:easy,medium,hard',
                'category' => 'required|string',
                'image' => 'nullable|string'
            ]);

            $recipe = Recipe::create($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Recipe created successfully',
                'data' => $recipe
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
            
            $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'content' => 'sometimes|required|string',
                'ingredients' => 'sometimes|required|array',
                'instructions' => 'sometimes|required|array',
                'cooking_time' => 'sometimes|required|integer|min:1',
                'servings' => 'sometimes|required|integer|min:1',
                'difficulty' => 'sometimes|required|in:easy,medium,hard',
                'category' => 'sometimes|required|string',
                'image' => 'nullable|string'
            ]);

            $recipe->update($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Recipe updated successfully',
                'data' => $recipe
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