<?php

namespace App\Http\Controllers;

use App\Models\Advert;
use App\Models\Product;
use App\Models\Category;

use Illuminate\Http\Request;
use App\Http\Requests\AdvertRequest;
use App\Http\Requests\UpdateAdvertRequest;

use App\Http\Resources\AdvertResource;
use App\Services\SlugCreateService;

use App\Services\CategoryService;

class AdvertController extends Controller
{
    
    protected SlugCreateService $slugCreateService;
    protected CategoryService $categoryService;

    public function __construct(SlugCreateService $slugCreateService, CategoryService $categoryService){
        $this->slugCreateService=$slugCreateService;
        $this->categoryService=$categoryService;
    }

    public function createAdvert(AdvertRequest $request){

        try {
            $data = $request->validated();
            $product= Product::findOrFail($data['product_id']);
            $data['category_id']=$product->category_id;
            $data['slug']=$this->slugCreateService->createSlug($data,\App\Models\Advert::class);


            if(Advert::where('product_id',$product->id)->exists()){
                return response()->json(['message'=>'Ürün için ilan oluşturulmuş'],400);

            }

            $advert=Advert::create($data);

            return response()->json(['message'=>'İlan oluşturuldu','advert'=>$advert],201);

        } catch (\Exception $e) {
             return response()->json(['error'=>$e->getMessage()],500);

        }
    }

    public function getAdvert($slug){
        try {
            $advert = Advert::where('slug',$slug)->with('product','product.images')->first();

            $category = Category::findOrFail($advert->category_id);
            $path = $this->categoryService->breadcrumb($category);

            if($advert){
                return response()->json([
                    'data'=>[
                        'advert'=>new AdvertResource($advert),
                        'bread_crumb'=>$path
                    ]
                    
                ]);
            }
            return response()->json('Ürün bulunamadı',404);
        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()],500);
        }
    }




    public function updateAdvert(UpdateAdvertRequest $request,$id){
        try {
            $data = $request->validated();

            $advert = Advert::findOrFail($id);

            $advert->update($data);
            return response()->json(['message'=>'Güncelleme başarılı.','advert'=>$advert],200);

        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);

        }
    }

    public function getAdverts(){
        try {
            $adverts = Advert::with('product')->orderBy('created_at','desc')->get();
            return response()->json(['adverts'=>$adverts],200);

        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }

    public function deleteAdvert($id){
        try {
            $advert = Advert::findOrFail($id);
            $advert->delete();
            return response()->json(['message'=>'Ürün silindi.'],200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error'=>'İlan bulunamadı.'],404);
        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }



}
