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

            return response()->json('Ürün oluşturuldu.',200);

        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }
}
