<?php

namespace App\Http\Controllers;

use App\Models\ProductImage;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductImageController extends Controller
{
    public static function media($product, $media){
        return url("products/".str_replace(' ', '_', $product))."/" . str_replace(' ', '_', $media);
    }
    public function index(Request $request)
    {
        $product = $request->product;
        return ProductImage::with(['product', 'productVariation'])->whereHas('product', function($query) use ($product) {
            $query->where('name', $product);
        })->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variation_id' => 'nullable|exists:product_variations,id',
            'media' => 'sometimes',
            'is_primary' => 'nullable|string', // Admin sends 'true' as string in FormData
            'kept_media_ids' => 'nullable|string'
        ]);

        $product = Product::findOrFail($request->product_id);
        logger('ProductImage upload request', $request->all());

        try {
            DB::beginTransaction();
            
            if ($request->has('kept_media_ids')) {
                $keptIds = json_decode($request->kept_media_ids, true);
                if (is_array($keptIds)) {
                    $obsoleteImages = ProductImage::where('product_id', $product->id)
                        ->whereNotIn('id', $keptIds)
                        ->get();
                        
                    foreach($obsoleteImages as $img) {
                        try {
                            $path_parts = explode('storage/products/', $img->url);
                            if (count($path_parts) > 1) {
                                $relativePath = 'storage/products/' . $path_parts[1];
                                $absolutePath = public_path($relativePath);
                                if (file_exists($absolutePath)) {
                                    unlink($absolutePath);
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error('Failed to unlink image: ' . $e->getMessage());
                        }
                        $img->delete();
                    }
                }
            }

            $imagesResponse = [];

            if ($request->hasFile('media')) {
                $mediaFiles = is_array($request->file('media')) ? $request->file('media') : [$request->file('media')];
                
                foreach ($mediaFiles as $index => $file) {
                    if($file && $file->isValid()){
                        $destinationPath = public_path("storage/products/") . str_replace(' ', '_', $product->name);
                        $name = str_replace(' ', '_', $file->getClientOriginalName());
                        $uniqueName = time() . '_' . substr($name, -150); // prevent too long names
                        $file->move($destinationPath, $uniqueName);
                        $url = url("storage/products/". str_replace(' ', '_', $product->name) ."/" . $uniqueName);
                        
                        $image = ProductImage::create([
                            'product_id' => $product->id,
                            'product_variation_id' => $request->product_variation_id,
                            'url' => $url,
                            'is_primary' => $request->is_primary === 'true' || (!isset($request->is_primary) && $index === 0 && count($imagesResponse) === 0)
                        ]);
                        $imagesResponse[] = $image;
                    }
                }
            }
            
            DB::commit();
            return response()->json(['success' => true, 'images' => $imagesResponse], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductImage upload failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Upload failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($product)
    {
        $image = ProductImage::with(['product', 'productVariation'])->whereHas('product', function($query) use ($product) {
            $query->where('name', $product);
        })->first();
        return response()->json($image);
    }

    public function update(Request $request, $id)
    {
        $image = ProductImage::findOrFail($id);
        $validated = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'product_variation_id' => 'nullable|exists:product_variations,id',
            'url' => 'sometimes|string',
            'is_primary' => 'nullable|boolean',
        ]);
        $image->update($validated);
        return response()->json($image->load(['product', 'productVariation']));
    }

    public function destroy($id)
    {
        $image = ProductImage::findOrFail($id);
        $image->delete();
        return response()->json(['message' => 'Product image deleted']);
    }
}
