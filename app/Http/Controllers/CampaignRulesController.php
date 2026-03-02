<?php

namespace App\Http\Controllers;

use App\Models\CampaignRules;
use App\Models\Campaign;
use App\Models\Advert;
use App\Models\ProductDiscount;

use App\Http\Resources\miniAdvertResource;
use App\Http\Resources\CampaignResource;

use Illuminate\Http\Request;

class CampaignRulesController extends Controller
{
    public function createRules(Request $request){
        try {
            $data['campaign_id']=$request->campaign_id;
            $data['field']=$request->field;
            $data['operator']=$request->operator;
            $data['value']=$request->value;
            
            CampaignRules::create($data);
            return response()->json('Kampanya kuralları oluşturuldu.');


        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()],500);
        }
    }
    

    public function getCampaignAdverts($slug){
        try {
            $campaign = Campaign::with('rules')->where('slug',$slug)->firstOrFail();

            $query=Advert::query();
            
            foreach($campaign->rules as $rule){

                if($rule->field==='price'){
                    $query->whereHas('product', function ($q) use ($rule) {
                        $q->where('price', $rule->operator, $rule->value);
                    });

                } else {
                    $query->where($rule->field, $rule->value);
                }

            }

            $adverts = $query->with('product')->get();
            foreach($adverts as $advert){
                $product=$advert->product;
                if(!$product) continue; 

                if($product->is_campaign_on){
                    $currentCampaign=Campaign::find($product->campaign_id);
                    if($currentCampaign->exclusive) continue;

                    if($campaign->priority > $currentCampaign->priority){
                        ProductDiscount::where('product_id',$product->id)
                        ->where('campaign_id',$currentCampaign->id)
                        ->update(['is_active'=>false]);
                    }else continue;
                       
                }


                $discountPrice=$this->calculateDiscount($product->price,$campaign);

                ProductDiscount::create([
                    'product_id'=>$product->id,
                    'campaign_id'=>$campaign->id,
                    'discount_price'=>$discountPrice,
                    'is_active' => true,
                ]);

                $product->campaign_id = $campaign->id;
                $product->is_campaign_on = true;
                $product->save();
            }

            return response()->json($adverts);

            return response()->json([
                'data'=>[
                    'campaign'=> new CampaignResource($campaign),
                    'adverts'=>miniAdvertResource::collection($adverts)
                ],
                'meta'=>[
                    'previous_page'=>$adverts->previousPageUrl(),
                    'current_page' => $adverts->currentPage(),
                    'last_page' => $adverts->lastPage(),
                    'total' => $adverts->total(),]
            ]);


        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()],500);
        }
    }


    

    public function getCampaignPage($slug){
        try {
            $campaign=Campaign::where('slug',$slug)->with('adverts')->firstOrFail();
            return response()->json($campaign);

        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()],500);

        }
    }






    public function calculateDiscount($price,$campaign){
        if($campaign->discount_type=='percent'){
            $discounted = $price - ($price * $campaign->discount_value / 100);
            return max(0, $discounted);

        }
        if($campaign->discount_type == 'fixed'){
            return max(0, $price - $campaign->discount_value);
        }
        return $price;
    }
}
