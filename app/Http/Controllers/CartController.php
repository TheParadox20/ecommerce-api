<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request){
        return response()->json([
            'temp'=>"Items",
        ]);
    }
    public function add(Request $request){
        $productID = $request->id;
        $request->session()->push('cart', $productID);
        return response()->json([
            'message'=>"Item {$productID} added",
            'cart'=>$request->session()->get('cart')
        ]);
    }
}
