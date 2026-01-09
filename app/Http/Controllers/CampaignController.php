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
        $data['image']=$path ?? null;
        Campaign::create($data);
        return response()->json('Kampanya oluşturuldu.',200);



    }

}
