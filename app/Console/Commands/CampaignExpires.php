<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campaign;
use App\Models\Product;

class CampaignExpires extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:campaign-expires';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Campaign::where('end_date','<=',now())
        ->where('is_active',true)
        ->each(function ($campaign){
            $products = Product::where('campaign_id',$campaign->id)
            ->with('activeDiscount')
            ->get();

            foreach($products as $product){ // şunu bi queue at amk 
                if($product->activeDiscount){
                    $product->activeDiscount->is_active = false;
                    $product->activeDiscount->save();
                }
                $product->update([
                    'campaign_id' => null,
                    'is_campaign_on' => false,
                ]);
                
            }
            $campaign->update(['is_active' => false]);

                
        });

        $this->info('Süresi dolan kampanyalar ve ürünler başarıyla pasife alındı!');
    }
}
