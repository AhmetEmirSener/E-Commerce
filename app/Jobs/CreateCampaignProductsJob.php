<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\ProductDiscount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\CalculateDiscountService;

class CreateCampaignProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(    
        public Campaign $campaign,
        public Collection $adverts,
        )
    {
    
    }

    /**
     * Execute the job.
     */
    public function handle(CalculateDiscountService $discountService): void
    {

        DB::transaction(function ()  use ($discountService){
            foreach($this->adverts as $advert){
                $product = $advert->product;
                if(!$product) continue; 
                
                if($product->is_campaign_on){
                    $currentCampaign=Campaign::find($product->campaign_id);
                    if (!$currentCampaign) {
                        $product->campaign_id    = null;
                        $product->is_campaign_on = false;
                    }else{

                
                        if($currentCampaign->exclusive) continue;

                        if($this->campaign->priority > $currentCampaign->priority){
                            ProductDiscount::where('product_id',$product->id)
                            ->where('is_active', true)
                            ->where('campaign_id',$currentCampaign->id)
                            ->update(['is_active'=>false]);
                        }else continue;
                    }
                       
                }
                $discountPrice = $discountService->calculateDiscount(
                    $product->price, 
                    $this->campaign  
                );
                ProductDiscount::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'campaign_id' => $this->campaign->id,
                    ],
                    [
                        'discount_price' => $discountPrice,
                        'discount_type'=>$this->campaign->discount_type,
                        'discount_value'=>$this->campaign->discount_value,
                        'is_active' => true,
                    ]
                );
                $product->campaign_id = $this->campaign->id;
                $product->is_campaign_on = true;
                $product->save();
            }
        });
    }
}
