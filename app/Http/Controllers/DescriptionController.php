<?php

namespace App\Http\Controllers;

use App\Models\Description;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DescriptionController extends Controller
{
    public function index()
    {
        // List all descriptions with their associated products
        return Description::with('product')->get();
    }

    public function store(Request $request)
    {
        try{
            $validated = $request->validate([
                'product_id' => 'required|unique:descriptions,product_id|exists:products,id',
                'description' => 'required|string',
            ]);
    
            $description = Description::create($validated);
            return response()->json(['success' => true, 'id' => $description->id], 201);
        }catch(\Illuminate\Validation\ValidationException $e){
            $description = Description::where('product_id', $request->product_id)->first();
            if($description){
                //update the description
                $description->update([
                    'description' => $request->description,
                    'product_id' => $request->product_id,
                ]);
                return response()->json(['success' => true, 'id' => $description->id], 201);
            }else{
                return response()->json(['success' => false, 'message' => 'update failed'], 422);
            }
        }catch(\Exception $e){
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, $product)
    {
        $description = Description::with('product.productImages')
            ->whereHas('product', function($query) use ($product) {
                $query->where('slug', $product)
                      ->orWhere('name', 'like', $product)
                      ->orWhere('id', $product);
            })
            ->first();
        return response()->json($description);
    }

    public function update(Request $request, $id)
    {
        $description = Description::findOrFail($id);
        
        $validated = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'description' => 'sometimes|string',
        ]);

        $description->update($validated);
        return response()->json($description->load('product'));
    }

    public function destroy($id)
    {
        $description = Description::findOrFail($id);
        $description->delete();
        return response()->json(['message' => 'Description deleted']);
    }
} 