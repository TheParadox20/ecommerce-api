<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Media;
use App\Models\ProductFAQ;
use App\Models\Description;
use App\Models\Variation;
use Illuminate\Support\Str;

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
        $query = Product::with(['media','category','brand']);
        
        if ($request->has('category')) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('categories.name', $request->category);
            });
        }
        
        if ($request->has('brand')) {
            $query->whereHas('brand', function($q) use ($request) {
                $q->where('brands.name', $request->brand);
            });
        }

        $products = $query->paginate(12);
        return response()->json($products);
    }

    public function create(Request $request){
        $category = Category::create(['name'=>$request->category]);
        $brand = Brand::create(['name'=>$request->brand]);
        $product = Product::create([
            'name'=>$request->name,
            'category_id'=>$category->id,
            'brand_id'=>$brand->id,
            'about'=>$request->about,
            'price'=>$request->price,
            'discount'=>$request->discount,
        ]);
        foreach ($request->faqs as $key => $value) {
            logger($request->faqs);
            ProductFAQ::create([
                'product_id'=>$product->id,
                'question'=>$value['question'],
                'answer'=>$value['answer'],
            ]);
        }
        foreach ($request->variations as $key => $value) {
            foreach ($value['options'] as $keyj => $valuej) {
                Variation::create([
                    'product_id'=>$product->id,
                    'option'=>$value->name,
                    'name'=>$valuej,
                ]);
            }
        }

        return response()->json([
            'success'=>true,
            'id'=>$product->id
        ]);
    }
    public function update(Request $request){}

    public function updateDescription(Request $request){
        try{
            if(!Description::where('product_id',$request->id)->first())
            Description::create([
                'product_id'=>$request->id,
                'description'=>$request->description
            ]);
            
            $product = Product::find($request->id);
            throw_if(!$product,'Missing product');

            // Cleanup old description media if new ones are being uploaded
            if ($request->hasFile("description0")) {
                $oldMedia = Media::where('product_id', $request->id)->where('purpose', 'description')->get();
                foreach ($oldMedia as $m) {
                    if ($m->url && str_contains($m->url, url('storage/products'))) {
                        $oldPath = str_replace(url(''), public_path(), $m->url);
                        if (file_exists($oldPath)) @unlink($oldPath);
                    }
                    $m->delete();
                }
            }

            for ($i=0; $i < 100; $i++) { 
                $file = $request->file("description$i");
                if($file==null){
                    logger("Files saved upto file :: $i");
                    break;
                }
                $folderName = $product->slug ?: Str::slug($product->name);
                if (empty($folderName)) $folderName = (string) $product->id;
                $destinationPath = public_path("storage/products/") . $folderName;
                if (!file_exists($destinationPath)) mkdir($destinationPath, 0755, true);

                $name = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                $file->move($destinationPath, $name);
                $url = url("storage/products/". $folderName ."/" . $name);
                Media::create([
                    'product_id'=>$request->id,
                    'purpose'=>'description',
                    'file'=>$name,
                    'url'=>$url,
                ]);
            }

            return response()->json([
                'message'=>'Description updated Succesfuly',
                'success'=>true
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function updateMedia(Request $request){
        try{
            $product = Product::find($request->id);
            throw_if(!$product,'Missing product');

            // Cleanup old gallery media if new ones are being uploaded
            if ($request->hasFile("media0")) {
                $oldMedia = Media::where('product_id', $request->id)->where('purpose', 'media')->get();
                foreach ($oldMedia as $m) {
                    if ($m->url && str_contains($m->url, url('storage/products'))) {
                        $oldPath = str_replace(url(''), public_path(), $m->url);
                        if (file_exists($oldPath)) @unlink($oldPath);
                    }
                    $m->delete();
                }
            }

            for ($i=0; $i < 100; $i++) { 
                $file = $request->file("media$i");
                if($file==null){
                    logger("Media Files saved upto file :: $i");
                    break;
                }
                $folderName = $product->slug ?: Str::slug($product->name);
                if (empty($folderName)) $folderName = (string) $product->id;
                $destinationPath = public_path("storage/products/") . $folderName;
                if (!file_exists($destinationPath)) mkdir($destinationPath, 0755, true);

                $name = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                $file->move($destinationPath, $name);
                $url = url("storage/products/". $folderName ."/" . $name);
                Media::create([
                    'product_id'=>$request->id,
                    'purpose'=>'media',
                    'file'=>$name,
                    'url'=>$url,
                ]);
            }

            return response()->json([
                'message'=>'Media updated Succesfuly',
                'success'=>true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
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
    public function adminListing(Request $request){
        return Product::select('id', 'name')->orderBy('name', 'asc')->get();
    }
    public function product(Request $request){
        try{
            $product = Product::where('name',$request->product)
                    ->with(['media','category','brand','review', 'variation', 'description','faq'])
                    ->first();
            
            throw_if(!$product,"Product $request->product not found");
            
            $related = Product::where('category_id',$product->category_id)
                            ->with(['media','category','brand'])
                            ->get();
            
            return response()->json([
                'product' => $product, 
                'related' => $related
            ]);
        } catch (Exception $e){
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
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
