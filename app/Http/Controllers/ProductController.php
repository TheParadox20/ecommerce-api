<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

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

        // Filter by status: storefront queries only get active products unless all_statuses or per_page (admin) is passed
        if ($request->filled('status')) {
            $products->where('status', $request->status);
        } else if (!$request->boolean('all_statuses') && !$request->has('per_page')) {
            $products->where(function ($q) {
                $q->where('status', 'active')->orWhereNull('status');
            });
        }

        // 🔎 Filter by category or brand
        if ($request->filled('category') && $request->filled('brand') && strtolower($request->category) === strtolower($request->brand)) {
            $slugValue = strtolower($request->category);
            $spacedValue = str_replace('-', ' ', $slugValue);
            $products->where(function($q) use ($slugValue, $spacedValue) {
                $q->whereHas('category', function ($query) use ($slugValue, $spacedValue) {
                    $query->whereRaw('LOWER(name) = ?', [$spacedValue])
                          ->orWhereRaw('LOWER(name) = ?', [$slugValue]);
                })->orWhereHas('brand', function ($query) use ($slugValue, $spacedValue) {
                    $query->whereRaw('LOWER(name) = ?', [$spacedValue])
                          ->orWhereRaw('LOWER(name) = ?', [$slugValue])
                          ->orWhereRaw('LOWER(slug) = ?', [$slugValue]);
                });
            });
        } else {
            if ($request->filled('category')) {
                $products->whereHas('category', function ($query) use ($request) {
                    $slugValue = strtolower($request->category);
                    $spacedValue = str_replace('-', ' ', $slugValue);
                    $query->whereRaw('LOWER(name) = ?', [$spacedValue])
                          ->orWhereRaw('LOWER(name) = ?', [$slugValue]);
                });
            }

            if ($request->filled('brand')) {
                $products->whereHas('brand', function ($query) use ($request) {
                    $slugValue = strtolower($request->brand);
                    $spacedValue = str_replace('-', ' ', $slugValue);
                    $query->whereRaw('LOWER(name) = ?', [$spacedValue])
                          ->orWhereRaw('LOWER(name) = ?', [$slugValue])
                          ->orWhereRaw('LOWER(slug) = ?', [$slugValue]);
                });
            }
        }

        // 🔎 Exclude a product by name (useful for "related products")
        if ($request->filled('exclude')) {
            $products->where('name', '!=', $request->exclude);
        }

        // 🔎 Search by name and about using Full-Text index
        if ($request->filled('search')) {
            $search = $request->search;
            $products->where(function($q) use ($search) {
                $q->whereFullText(['name', 'about'], $search)
                  ->orWhere('name', 'like', '%' . $search . '%'); // Fallback for very short terms
            });
        }

        // 🏷️ Filter by offers (discounted products)
        if ($request->boolean('offers')) {
            $products->where(function ($query) {
                $query->where('discount', '>', 0)
                    ->orWhereHas('productVariations', function ($q) {
                        $q->where('discount', '>', 0);
                    });
            });
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

        // 📄 Pagination — admin can pass per_page to fetch all products at once
        $perPage = (int) $request->get('per_page', 20);
        // Cap to prevent abuse; 500 is more than enough for admin listings
        $perPage = min($perPage, 500);

        return response()->json(
            $products->paginate($perPage)
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
            'name' => ['required', 'string', Rule::unique('products', 'name')->whereNull('deleted_at')],
            'category' => 'required|string',
            'category_id' => 'sometimes|exists:categories,id',
            'brand' => 'nullable|string',
            'brand_id' => 'nullable|exists:brands,id',
            'about' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'status' => 'nullable|string|in:active,inactive,draft',
            'is_promoted' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            if (!empty($validated['brand'])) {
                $brand = \App\Models\Brand::firstOrCreate([
                    'name' => $validated['brand']
                ]);
                $validated['brand_id'] = $brand->id;
                unset($validated['brand']);
            }

            if (isset($validated['category'])) {
                $categoryData = ['name' => $validated['category']];
                if (isset($validated['brand_id'])) {
                    $categoryData['brand_id'] = $validated['brand_id'];
                }
                $category = \App\Models\Category::firstOrCreate($categoryData);
                $validated['category_id'] = $category->id;
                unset($validated['category']);
            }

            // Generate slug automatically (handling soft-deleted conflicts)
            $validated['slug'] = $this->generateUniqueSlug($validated['name']);

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
                                    'sku' => $this->generateUniqueSku($product->name, $attributeValue, $product->id),
                                    'price' => $details['price'] ?? $product->price ?? 0,
                                    'stock' => $details['stock'] ?? 0,
                                    'discount' => $details['discount'] ?? null,
                                    'image' => $details['image'] ?? null,
                                    'weight_kg' => $details['weight_kg'] ?? 0,
                                    'min_order_quantity' => $details['min_order_quantity'] ?? 0,
                                    'is_bulk' => $details['is_bulk'] ?? false,
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
            'approvedReviews'
        ])->where('slug', $identifier)
          ->orWhere('name', $identifier)
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
            'name' => ['sometimes', 'string', Rule::unique('products', 'name')->ignore($id)->whereNull('deleted_at')],
            'category' => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
            'brand' => 'nullable|string',
            'brand_id' => 'nullable|exists:brands,id',
            'about' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'version' => 'nullable|integer',
            'status' => 'nullable|string|in:active,inactive,draft',
            'is_promoted' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            if (isset($validated['brand'])) {
                $brand = \App\Models\Brand::firstOrCreate([
                    'name' => $validated['brand']
                ]);
                $validated['brand_id'] = $brand->id;
                unset($validated['brand']);
            }

            if (isset($validated['category'])) {
                $categoryData = ['name' => $validated['category']];
                if (isset($validated['brand_id'])) {
                    $categoryData['brand_id'] = $validated['brand_id'];
                } elseif (isset($product->brand_id)) {
                    $categoryData['brand_id'] = $product->brand_id;
                }
                $category = \App\Models\Category::firstOrCreate($categoryData);
                $validated['category_id'] = $category->id;
                unset($validated['category']);
            }

            if (isset($validated['name'])) {
                $validated['slug'] = $this->generateUniqueSlug($validated['name'], $product->id);
            }

            // Remove version from validated array if present, as updateOptimistically handles it internally
            // but we need it for expectedVersion
            $expectedVersion = $validated['version'] ?? null;
            unset($validated['version']);

            if ($expectedVersion !== null) {
                $product->updateOptimistically($validated, $expectedVersion);
            } else {
                $product->update($validated);
            }

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
                                $sku = $this->generateUniqueSku($product->name, $attributeValue, $product->id);
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
                                        'image' => $details['image'] ?? null,
                                        'weight_kg' => $details['weight_kg'] ?? 0,
                                        'min_order_quantity' => $details['min_order_quantity'] ?? 0,
                                        'is_bulk' => $details['is_bulk'] ?? false,
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
            
            $statusCode = str_contains($e->getMessage(), 'Conflict detected') ? 409 : 500;
            
            return response()->json([
                'success' => false,
                'message' => $statusCode === 409 ? 'Conflict detected: The product was updated by someone else.' : 'Failed to update product.',
                'error' => $e->getMessage()
            ], $statusCode);
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

    /**
     * Generate a unique slug for a product, resolving conflicts with soft-deleted or existing products.
     *
     * @param string $name
     * @param int|null $ignoreId
     * @return string
     */
    protected function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        if (empty($baseSlug)) {
            $baseSlug = 'product';
        }

        $slug = $baseSlug;
        $count = 1;

        while (true) {
            $query = Product::withTrashed()->where('slug', $slug);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
            $existing = $query->first();

            if (!$existing) {
                return $slug;
            }

            if ($existing->trashed()) {
                $existing->update([
                    'slug' => 'deleted_' . $existing->id . '_' . $slug
                ]);
                return $slug;
            }

            $slug = $baseSlug . '-' . $count;
            $count++;
        }
    }

    /**
     * Generate a unique SKU for a product variation, resolving conflicts with soft-deleted or existing variations.
     *
     * @param string $productName
     * @param string $attributeValue
     * @param int $productId
     * @return string
     */
    protected function generateUniqueSku(string $productName, string $attributeValue, int $productId): string
    {
        $baseSku = strtoupper(Str::slug($productName . '-' . $attributeValue));
        if (empty($baseSku)) {
            $baseSku = 'SKU-' . $productId;
        }

        $sku = $baseSku;
        $count = 1;

        while (true) {
            $existing = \App\Models\ProductVariation::where('sku', $sku)->first();

            if (!$existing || $existing->product_id === $productId) {
                return $sku;
            }

            if ($existing->product && $existing->product->trashed()) {
                $existing->update([
                    'sku' => 'DELETED_' . $existing->id . '_' . $sku
                ]);
                return $sku;
            }

            $sku = $baseSku . '-' . $count;
            $count++;
        }
    }
}