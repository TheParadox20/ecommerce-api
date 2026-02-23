<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'about' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'stock' => 'nullable|integer|min:0',
        ]);

        // Generate slug automatically
        $validated['slug'] = Str::slug($validated['name']);

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'id' => $product->id,
            'product' => $product
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW SINGLE PRODUCT (BY SLUG)
    |--------------------------------------------------------------------------
    */
    public function show($slug)
    {
        $product = Product::with([
            'productVariations.attributeValues.attribute',
            'productImages',
            'category',
            'brand',
            'faqs',
            'reviews'
        ])->where('slug', $slug)->firstOrFail();

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
            'category_id' => 'sometimes|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'about' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'stock' => 'nullable|integer|min:0',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $product->update($validated);

        return response()->json([
            'success' => true,
            'product' => $product->load([
                'productVariations.attributeValues.attribute',
                'productImages',
                'category',
                'brand'
            ])
        ]);
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