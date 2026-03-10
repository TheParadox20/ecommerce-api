<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST PRODUCTS (SHOP PAGE)
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $products = Product::with([
            'productVariations.attributeValues.attribute',
            'productImages',
            'category',
            'brand'
        ]);

        // 🔎 Filter by category
        if ($request->filled('category')) {
            $products->whereHas('category', function ($query) use ($request) {
                $query->where('name', $request->category);
            });
        }

        // 🔎 Filter by brand
        if ($request->filled('brand')) {
            $products->whereHas('brand', function ($query) use ($request) {
                $query->where('name', $request->brand);
            });
        }

        // 🔎 Search by name
        if ($request->filled('search')) {
            $products->where('name', 'like', '%' . $request->search . '%');
        }

        // 💰 Price range filter
        if ($request->filled('min_price')) {
            $products->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $products->where('price', '<=', $request->max_price);
        }

        // 📊 Sorting
        if ($request->filled('sort_by')) {
            $sort = $request->sort_by;

            if ($sort === 'price_asc') {
                $products->orderBy('price', 'asc');
            } elseif ($sort === 'price_desc') {
                $products->orderBy('price', 'desc');
            } elseif ($sort === 'newest') {
                $products->orderBy('created_at', 'desc');
            }
        } else {
            $products->latest();
        }

        // 📄 Pagination (IMPORTANT)
        return response()->json(
            $products->paginate(20)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE PRODUCT (CREATE)
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:products,name',
            'category' => 'required|string',
            'category_id' => 'sometimes|exists:categories,id',
            'brand' => 'nullable|string',
            'brand_id' => 'nullable|exists:brands,id',
            'about' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'stock' => 'nullable|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            if (isset($validated['category'])) {
                $category = \App\Models\Category::firstOrCreate(['name' => $validated['category']]);
                $validated['category_id'] = $category->id;
                unset($validated['category']);
            }

            if (!empty($validated['brand'])) {
                $brand = \App\Models\Brand::firstOrCreate([
                    'name' => $validated['brand'],
                    'category_id' => $validated['category_id'] ?? null
                ]);
                $validated['brand_id'] = $brand->id;
                unset($validated['brand']);
            }

            // Generate slug automatically
            $validated['slug'] = Str::slug($validated['name']);

            $product = Product::create($validated);

            if ($request->has('faqs') && is_array($request->input('faqs'))) {
                foreach ($request->input('faqs') as $faq) {
                    if (isset($faq['question']) && isset($faq['answer'])) {
                        $product->faqs()->create([
                            'question' => $faq['question'],
                            'answer' => $faq['answer'],
                        ]);
                    }
                }
            }

            if ($request->has('attributes') && is_array($request->input('attributes'))) {
                foreach ($request->input('attributes') as $attributeName => $values) {
                    if (is_array($values)) {
                        foreach ($values as $attributeValue => $details) {
                            if (is_array($details)) {
                                \App\Models\ProductVariation::create([
                                    'product_id' => $product->id,
                                    'attribute_name' => $attributeName,
                                    'attribute_value' => $attributeValue,
                                    'sku' => strtoupper(\Illuminate\Support\Str::slug($product->name . '-' . $attributeValue)),
                                    'price' => $details['price'] ?? $product->price ?? 0,
                                    'stock' => $details['stock'] ?? 0,
                                    'discount' => $details['discount'] ?? null,
                                    'status' => 'active',
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();

            // load few relationships for the response
            return response()->json([
                'success' => true,
                'id' => $product->id,
                'product' => [
                    'id' => $product->id,
                    'slug' => $product->slug,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW SINGLE PRODUCT (BY SLUG)
    |--------------------------------------------------------------------------
    */
    public function show($identifier)
    {
        $product = Product::with([
            'productVariations.attributeValues.attribute',
            'productImages',
            'category',
            'brand',
            'faqs',
            'description',
            'reviews'
        ])->where('slug', $identifier)
          ->orWhere('id', $identifier)
          ->firstOrFail();

        return response()->json($product);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PRODUCT
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|unique:products,name,' . $id,
            'category' => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
            'brand' => 'nullable|string',
            'brand_id' => 'nullable|exists:brands,id',
            'about' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'stock' => 'nullable|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            if (isset($validated['category'])) {
                $category = \App\Models\Category::firstOrCreate(['name' => $validated['category']]);
                $validated['category_id'] = $category->id;
                unset($validated['category']);
            }
            
            if (isset($validated['brand'])) {
                $brand = \App\Models\Brand::firstOrCreate([
                    'name' => $validated['brand'],
                    'category_id' => $validated['category_id'] ?? null
                ]);
                $validated['brand_id'] = $brand->id;
                unset($validated['brand']);
            }

            if (isset($validated['name'])) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            $product->update($validated);

            // Sync FAQs
            if ($request->has('faqs') && is_array($request->input('faqs'))) {
                $product->faqs()->delete();
                foreach ($request->input('faqs') as $faq) {
                    if (isset($faq['question']) && isset($faq['answer'])) {
                        $product->faqs()->create([
                            'question' => $faq['question'],
                            'answer' => $faq['answer'],
                        ]);
                    }
                }
            }

            // Sync Attributes/Variations
            if ($request->has('attributes') && is_array($request->input('attributes'))) {
                $existingSkus = [];
                foreach ($request->input('attributes') as $attributeName => $values) {
                    if (is_array($values)) {
                        foreach ($values as $attributeValue => $details) {
                            if (is_array($details)) {
                                $sku = strtoupper(\Illuminate\Support\Str::slug($product->name . '-' . $attributeValue));
                                $existingSkus[] = $sku;
                                \App\Models\ProductVariation::updateOrCreate(
                                    [
                                        'product_id' => $product->id,
                                        'sku' => $sku
                                    ],
                                    [
                                        'attribute_name' => $attributeName,
                                        'attribute_value' => $attributeValue,
                                        'price' => $details['price'] ?? $product->price ?? 0,
                                        'stock' => $details['stock'] ?? 0,
                                        'discount' => $details['discount'] ?? null,
                                        'status' => 'active',
                                    ]
                                );
                            }
                        }
                    }
                }
                
                // Delete variations that are no longer present
                if (!empty($existingSkus)) {
                    \App\Models\ProductVariation::where('product_id', $product->id)
                                                ->whereNotIn('sku', $existingSkus)
                                                ->delete();
                } else if ($request->has('attributes') && empty($request->input('attributes'))) {
                    // Empty attributes passed, clear all
                    \App\Models\ProductVariation::where('product_id', $product->id)->delete();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'slug' => $product->slug
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE PRODUCT (SOFT DELETE)
    |--------------------------------------------------------------------------
    */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATED PRODUCTS
    |--------------------------------------------------------------------------
    */
    public function related($slug)
    {
        $product = Product::where('slug', $slug)->first();

        if (!$product) {
            return response()->json([]);
        }

        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['productImages', 'category', 'brand'])
            ->limit(6)
            ->get();

        return response()->json($related);
    }
}