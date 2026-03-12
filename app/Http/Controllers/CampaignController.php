<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use App\Http\Requests\CampaignStoreRequest;
use App\Http\Requests\CampaignUpdateRequest;
use Illuminate\Support\Facades\File;

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

    public function updateCampaign(CampaignUpdateRequest $request,$slug){
        try {
            $campaign=Campaign::where('slug',$slug)->firstOrFail();
            $data = $request->validated();
            // servis aç upload delete
            if($request->hasFile('image')){
                if ($campaign->image && Storage::disk('public')->exists($campaign->image)) {
                    Storage::disk('public')->delete($campaign->image);
                }
                $data['image']=$request->file('image')->store('uploads','public');
            }
            if($request->hasFile('mobile_image')){
                if ($campaign->mobile_image && Storage::disk('public')->exists($campaign->mobile_image)) {
                    Storage::disk('public')->delete($campaign->mobile_image);
                }
                $data['mobile_image']=$request->file('mobile_image')->store('uploads','public');

            }
            $campaign->update($data);
            return response()->json('Update başarılı');

        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()]);
        }
    }



}
