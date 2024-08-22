<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function index(Request $request){
        $product = [
            'id'=>'AX87OZ',
            'image'=>url("products/".str_replace(' ', '_', 'Product name'))."/" . str_replace(' ', '_', '/GrainmillOatsEdited.png'),
            'title'=>'Product title',
            'name'=>'Product name and description',
            'price'=>400,
            'previous'=>500,
            'message'=>'Best seller'
        ];
        $products = array_fill(0, 16, $product);
        return response()->json($products);
    }
    public function listing(Request $request){
        $product = [
            'id'=>'AX87OZ',
            'image'=>url("products/".str_replace(' ', '_', 'Product name'))."/" . str_replace(' ', '_', '/GrainmillOatsEdited.png'),
            'title'=>'Product title',
            'name'=>'Product name and description',
            'price'=>400,
            'previous'=>500,
            'message'=>'Best seller'
        ];
        $products = array_fill(0, 16, $product);
        return response()->json($products);
    }
}
