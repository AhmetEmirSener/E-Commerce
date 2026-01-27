<?php

namespace App\Http\Controllers;

use App\Models\Advert;
use Illuminate\Http\Request;
use App\Http\Requests\AdvertRequest;
use App\Http\Requests\UpdateAdvertRequest;

use App\Http\Resources\AdvertResource;

class AdvertController extends Controller
{

    
    public function createAdvert(AdvertRequest $request){

        try {
            $data = $request->validated();
            $product_id=$data['product_id'];
           
            if(Advert::where('product_id',$product_id)->exists()){
                return response()->json(['message'=>'Ürün için ilan oluşturulmuş'],400);

            }

            $advert=Advert::create($data);

            return response()->json(['message'=>'İlan oluşturuldu','advert'=>$advert],200);

        } catch (\Exception $e) {
             return response()->json(['error'=>$e->getMessage()],500);

        }
    }

    public function getAdvert($slug){
        try {
            $advert = Advert::where('slug',$slug)->with('product','product.images')->first();
            if($advert){
                return new AdvertResource($advert);
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
