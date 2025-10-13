<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProductRequest;
use Illuminate\Support\Str;



class ProductController extends Controller
{
    public function createProduct(StoreProductRequest $request){
        try{
            $slug = Str::slug($request->name);
            $slugCount= Product::where('slug','LIKE',"{$slug}%")->count();

            if($slugCount>0){
                $slug.='-'. ($slugCount+1);
            }

            $data = $request->validated();
            Product::create([...$data, 'slug'=>$slug]);

            return response()->json(['message' => 'Ürün oluşturuldu.'], 200);

        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }



    public function updateProduct(StoreProductRequest $request,$id){
        try {
            $data = $request->validated();


            $product = Product::findOrFail($id);
                if($product->name!==$data['name']){
                    $slug= Str::slug($data['name']);
                    $slugCount= Product::where('slug','LIKE',"{$slug}%")->count();
                    if($slugCount>0){
                        $slug.='-'.($slugCount+1);
                    }
                    $data['slug'] = $slug;
                }
            $product->update($data);

            return response()->json(['message' => 'Ürün güncelleme başarılı'], 200);


        } catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }


    public function getProducts(){
        try {
            $data= Product::with('category')->get();
            return response()->json(['Ürünler',$data],200);
            
        } catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }

    public function deleteProduct(Request $request,$id){
        try {
            $product= Product::findOrFail($id);
            $product->delete();
            return response()->json(['message' => 'Silme işlemi başarılı'], 200);

        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }
}
