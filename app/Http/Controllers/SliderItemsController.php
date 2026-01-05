<?php

namespace App\Http\Controllers;

use App\Models\SliderItems;
use Illuminate\Http\Request;
use App\Http\Requests\StoreSliderItems;


class SliderItemsController extends Controller
{

    public function store(StoreSliderItems $request){
        try {
            $data = $request->validated();
            
            $sliderItem = SliderItems::create($data);

            return response()->json(['message'=>'Slider Oluşturuldu.',$sliderItem],200);

        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }


}
