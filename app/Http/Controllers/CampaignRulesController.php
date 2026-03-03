<?php

namespace App\Http\Controllers;

use App\Models\CampaignRules;
use App\Models\Campaign;
use App\Models\Advert;
use App\Models\ProductDiscount;
use App\Models\CampaignAdverts;

use App\Http\Resources\miniAdvertResource;
use App\Http\Resources\CampaignResource;

use App\Jobs\CreateCampaignProductsJob;
use App\Services\CalculateDiscountService;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CampaignRulesController extends Controller
{
    protected CalculateDiscountService $discountService;
    
    public function __construct(CalculateDiscountService $discountService){
        $this->discountService =$discountService;
    }



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

    public function createCampaignExclusives(Request $request){
        try {
            $data['campaign_id']=$request->campaign_id;
            $data['advert_id']=$request->advert_id;
            $data['type']=$request->type;

            CampaignAdverts::create($data);
            return response()->json('Oluşturuldu');

        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()],500);

        }
    }
    

    public function createCampaignProducts($slug){
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

            CreateCampaignProductsJob::dispatch($campaign,$adverts);

            return response()->json(['message' => 'Kampanya ürünleri oluşturuluyor.']);



        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()],500);
        }
    }



    public function attachProduct($slug,$advertId){
        try {
            $campaign=Campaign::where('slug',$slug)->firstOrFail();
            $advert = Advert::with('product')->findOrFail($advertId);
            $product=$advert->product;
            if (!$product) return response()->json(['message' => 'Ürün bulunamadı'], 404);


            $otherCampaignDiscount = ProductDiscount::where('product_id', $product->id)
            ->where('campaign_id', '!=', $campaign->id)
            ->where('is_active', true)
            ->first();
            

            if($otherCampaignDiscount){
                $otherCampaign= Campaign::find($otherCampaignDiscount->campaign_id);

                if ($otherCampaign->exclusive) {
                    return response()->json(['message' => 'Ürün başka bir kampanyada'], 409);
                }
                
                if ($campaign->priority <= $otherCampaign->priority) {
                    return response()->json(['message' => 'Mevcut kampanyanın önceliği daha yüksek'], 409);
                }

                $otherCampaignDiscount->update(['is_active' => false]);

            }

            $exists= ProductDiscount::where('product_id',$product->id)
            ->where('campaign_id', $campaign->id)
            ->first();
            if($exists){
                if($exists->is_active){
                    return response()->json(['message'=>'Zaten ekli'],409);
                }
                else{
                    DB::transaction(function () use ($exists,$product,$campaign,$advertId){
                        $newDiscountPrice = $this->discountService->calculateDiscount($product->price, $campaign); 

                        $exists->update([
                            'is_active'      => true,
                            'discount_price' => $newDiscountPrice, 
                        ]);
                        $product->update(['campaign_id'=>$campaign->id,'is_campaign_on'=>true]);
                        
                        CampaignAdverts::updateOrCreate(
                            ['campaign_id' => $campaign->id, 'advert_id' => $advertId],
                            ['type' => 'include']
                        );
                    });

                    return response()->json(['message'=>'Ürün güncellenerek aktif oldu.'],200);
                }
            }
          
            $discountPrice=$this->discountService->calculateDiscount($product->price, $campaign); 


            DB::transaction(function () use ($product,$campaign,$discountPrice,$advertId){
                ProductDiscount::create([
                    'product_id'   => $product->id,
                    'campaign_id'  => $campaign->id,
                    'discount_price' => $discountPrice,
                    'is_active'    => true,
                ]);

                $product->update(['campaign_id' => $campaign->id, 'is_campaign_on' => true]);
                CampaignAdverts::updateOrCreate(
                    ['campaign_id' => $campaign->id, 'advert_id' => $advertId],
                    ['type' => 'include']
                );
            });
   
            return response()->json(['message' => 'Eklendi']);


        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()],500);

        }
    }


    public function detachProduct($slug,$advertId){
        $campaign=Campaign::where('slug',$slug)->firstOrFail();
        $advert = Advert::with('product')->findOrFail($advertId);
        $product=$advert->product;
    
        DB::transaction(function () use ($campaign,$product,$advertId){
            ProductDiscount::where('product_id', $product->id)
            ->where('campaign_id', $campaign->id)
            ->update(['is_active' => false]);
    
            $product->update(['campaign_id' => null, 'is_campaign_on' => false]);
    
            CampaignAdverts::where('campaign_id', $campaign->id)
            ->where('advert_id', $advertId)
            ->delete();
        });
 

        return response()->json(['message' => 'Çıkarıldı']);

    }

    

    public function getCampaignPage($slug){
        try {
            $campaign=Campaign::where('slug',$slug)->firstOrFail();

            $adverts = $campaign->activeProductDiscounts()
            ->paginate(12);
            return response()->json([
                'campaign'=>new CampaignResource($campaign),
                'data'=>miniAdvertResource::collection($adverts),
                'meta' => [
                    'current_page' => $adverts->currentPage(),
                    'last_page' => $adverts->lastPage(),
                    'per_page' => $adverts->perPage(),
                    'total' => $adverts->total(),
                ],
            ]);
         


        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()],500);

        }
    }







}
