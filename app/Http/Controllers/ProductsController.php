<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductsController extends Controller
{
    /**
     * Given product name and media generate url pointing to resource on server
     * @param string $product
     * @param string $media file name
     * @return string
     */
    public static function media($product, $media){
        return url("products/".str_replace(' ', '_', $product))."/" . str_replace(' ', '_', $media);
    }
    public function index(Request $request){
        $product = [
            'id'=>'AX87OZ',
            'image'=>ProductsController::media('Product name','GrainmillOatsEdited.png'),
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
            'image'=>ProductsController::media('Product name','GrainmillOatsEdited.png'),
            'title'=>'Product title',
            'name'=>'Product name and description',
            'price'=>400,
            'previous'=>500,
            'message'=>'Best seller'
        ];
        $products = array_fill(0, 16, $product);
        return response()->json($products);
    }
    public function related(Request $request){
        $product = [
            'id'=>'AX87OZ',
            'image'=>ProductsController::media('Product name','GrainmillOatsEdited.png'),
            'title'=>'Product title',
            'name'=>'Product name and description',
            'price'=>400,
            'previous'=>500,
            'message'=>'Best seller'
        ];
        $products = array_fill(0, 4, $product);
        return response()->json($products);
    }
}
