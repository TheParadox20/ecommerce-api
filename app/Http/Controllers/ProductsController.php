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
    public function product(Request $request){
        $product = [
            'id'=>'AX065',
            'media'=>[
                [
                    'type'=>'image',
                    'src'=>ProductsController::media('Product name','GrainmillOatsEdited.png'),
                ],
                [
                    'type'=>'image',
                    'src'=>ProductsController::media("Product name/media",'corp.jpg'),
                ],
                [
                    'type'=>'image',
                    'src'=>ProductsController::media("Product name/media",'stone.jpg')
                ]
            ],
            'title'=>'Product title',
            'name'=>'Product name slightly longer',
            'price'=>400,
            'previous'=>500,
            'description'=> 'Lorem ipsum dolor sit, amet consectetur adipisicing elit. Minima laborum iusto delectus numquam, quod blanditiis odio vitae facere tempore corrupti repellat ipsam. Repellendus culpa iste perspiciatis perferendis cupiditate velit repudiandae!',
            'reviews'=>230,
            'sold'=>623,
            'rating'=>4.5,
            'perks'=>['Free shipping on orders over Ksh 300','Free + easy returns'],
            'options'=>[
                [
                    'name'=>'size',
                    'categories'=>['250g', '500g', '1Kg']
                ]
            ]
        ];
        return response()->json($product);
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
        $products = array_fill(0, 10, $product);
        return response()->json($products);
    }
    public function details(Request $request){
        $detail = [
            'media'=>[
                [
                    'type'=>'image',
                    'src'=>'/GrainmillOatsEdited.png',
                ]
            ],
            'about'=>'Lorem ipsum dolor sit amet consectetur adipisicing elit. Blanditiis voluptates nihil dicta quod consectetur, sit dolor doloremque exercitationem iste odit ab ducimus sint architecto, sapiente harum totam. Dignissimos, culpa ducimus.',
            'extras'=>[
                [
                    'title'=>'Laboris consequat ad',
                    'content'=>'Lorem ipsum dolor sit amet consectetur adipisicing elit. Blanditiis voluptates nihil dicta quod consectetur, sit dolor doloremque exercitationem iste odit ab ducimus sint architecto, sapiente harum totam. Dignissimos, culpa ducimus.'
                ],
                [
                    'title'=>'Deserunt ex',
                    'content'=>'Lorem ipsum dolor sit amet consectetur adipisicing elit. Blanditiis voluptates nihil dicta quod consectetur, sit dolor doloremque exercitationem iste odit ab ducimus'
                ]
            ]
        ];
        return response()->json($detail);
    }
    public function reviews(Request $request){
        $reviews = [
            'rating'=>4.5,
            'reviews'=>230,
            'ratings'=>[60, 25, 10, 5, 0],
            'reviewers'=> [
                [
                    'name'=>'Jane Doe',
                    'icon'=>'',
                    'date'=>'3d ago',
                    'rating'=>3.5,
                    'comment'=>'Lorem ipsum dolor sit amet consectetur adipisicing elit. Blanditiis voluptates nihil dicta quod consectetur, sit dolor doloremque exercitationem iste odit ab ducimus sint architecto, sapiente harum totam. Dignissimos, culpa ducimus.'
                ]
            ]
        ];
        return response()->json($reviews);
    }

    public function faqs(Request $request){
        $question = [
            'question'=>'Delectus molestiae iure ea! Dolor quidem maiores',
            'answer'=>'Lorem ipsum, dolor sit amet consectetur adipisicing elit. Aspernatur cumque quae, delectus molestiae iure ea! Dolor quidem maiores repudiandae nam hic earum laborum neque, quae quia similique? Repudiandae, omnis illum!'
        ];
        $questions = array_fill(0, 8, $question);
        return response()->json($questions);
    }
}
