<?php

namespace App\Http\Controllers;

use App\Models\Slider;
use App\Models\SliderItems;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Requests\StoreSlider;
use App\Http\Resources\SliderResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\AdvertResource;
use App\Http\Resources\popularAdvertsByCate;
use App\Http\Resources\MiniAdvertResource
;

class SliderController extends Controller
{

    public function store(StoreSlider $request){
        try {
            $data = $request->validated();

            $slider = Slider::create($data);

            return response()->json(['Message'=>'Slider oluşturuldu.',$slider],200);
        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }


    
    public function getSlider(Request $request, $sliderName){
        try {
            if($sliderName==='advertPage' && $request->category_id){
                $popularAdverts = Category::where('id',$request->category_id)->with('popularAdverts')->get();
                return response()->json($popularAdverts);
            }

            $slider = Slider::where('page',$sliderName)->with([
                'items',
                'items.advert.product',
                'items.category',
                'items.campaign'
            ])->get()->sortBy('sort');

            if(empty($slider)){
                return response()->json(['message'=>'Slider bulunamadı'],400);
            }
            
    

            
            return SliderResource::collection($slider);

        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }

    public function popularAdvertsByCategory($categoryId,$productId){
        try {
            $category = Category::with('popularAdverts.product')
            ->findOrFail($categoryId);

            $popularAdverts = $category->popularAdverts()
            ->where('products.id', '!=', $productId)
            ->get();

            return MiniAdvertResource::collection($popularAdverts);

        }catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }


}
