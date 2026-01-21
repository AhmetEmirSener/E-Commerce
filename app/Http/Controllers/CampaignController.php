<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use App\Http\Requests\CampaignStoreRequest;

class CampaignController extends Controller
{

    public function createCampaign(CampaignStoreRequest $request){
        $data = $request->validated();

        if($request->hasFile('image')){
            $path=$request->image->store('uploads','public');
        }
        if($request->hasFile('mobile_image')){
            $pathMo=$request->mobile_image->store('uploads','public');
        }
        $data['image']=$path ?? null;
        $data['mobile_image']=$pathMo ?? null;
        Campaign::create($data);
        return response()->json('Kampanya oluşturuldu.',200);



    }

}
