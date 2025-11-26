<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['productVariations.attributeValues.attribute', 'productImages', 'category']);
        if($request->has('category')){
            logger('category' . $request->input('category'));
            $products = $products->whereHas('category', function($query) use ($request) {
                $query->where('name', $request->input('category'));
            });
            logger('products' . $products->get()->toJson());
        }
        if($request->has('brand')){
            logger('brand' . $request->input('brand'));
            $products = $products->whereHas('brand', function($query) use ($request) {
                $query->where('name', $request->input('brand'));
            });
        }
        // return Product::with(['productVariations.attributeValues.attribute', 'productImages', 'category'])->get();
        // List all products with variations, images, and category
        return $products->get();
    }

    public function store(Request $request)
    {
        try{
            $validated = $request->validate([
                'name' => 'required|string|unique:products,name',
                'category_id' => 'required|exists:categories,id',
                'brand_id' => 'nullable|exists:brands,id',
                'about' => 'nullable|string',
                'price' => 'nullable|numeric',
                'discount' => 'nullable|numeric',
                'stock' => 'nullable|numeric',
            ]);
            $product = Product::create($validated);
            return response()->json(['success'=>true, 'id'=>$product->id], 201);
        }catch(\Illuminate\Validation\ValidationException $e){
            $errors = $e->validator->errors();
            logger('Product Validation Error', ['errors' => $errors]);
            $existingProduct = Product::where('name', $request->input('name'))->first();
            if ($existingProduct) {
                $updateData = $request->only([
                    'category_id',
                    'brand_id',
                    'about',
                    'price',
                    'discount',
                    'stock'
                ]);
                $existingProduct->update(array_filter($updateData, function($v) { return !is_null($v); }));
                return response()->json(['success'=>true, 'id'=>$existingProduct->id, 'updated'=>true], 200);
            }else logger('Product not found');
        }
        catch(\Exception $e){
            return response()->json(['success'=>false, 'message'=>$e->getMessage()], 500);
        }
    }

    public function show($name)
    {
        $product = Product::with(['productVariations.attributeValues.attribute', 'productImages', 'category', 'brand', 'faq', 'review'])->where('name', $name)->first();
        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|unique:products,name,' . $id,
            'category_id' => 'sometimes|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'about' => 'nullable|string',
            'price' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
        ]);
        $product->update($validated);
        return response()->json($product->load(['productVariations.attributeValues.attribute', 'productImages', 'category']));
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['message' => 'Product deleted']);
    }

    public function related(Request $request, $product_name)
    {
        $product = Product::where('name', $product_name)->first();
        if (!$product) {
            return response()->json([], 200);
        }
        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['productImages', 'category', 'brand'])
            ->get();
        return response()->json($related);
    }
}
