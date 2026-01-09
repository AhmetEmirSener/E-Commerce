<?php

namespace App\Http\Controllers;

use App\Models\Slider;
use App\Models\SliderItems;

use Illuminate\Http\Request;
use App\Http\Requests\StoreSlider;
use App\Http\Resources\SliderResource;


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


    
    public function getSlider($sliderName){
        try {
            $slider = Slider::where('page',$sliderName)->with([
                'items',
                'items.advert.product',
                'items.category'
            ])->get()->sortBy('sort');

            if(empty($slider)){
                return response()->json(['message'=>'Slider bulunamadı'],400);
            }
            
            return SliderResource::collection($slider);

        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }


}
