<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Product;
use App\Models\ProductDiscount;
use App\Services\CalculateDiscountService;


class UpdateCampaignDiscountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct( public Product $product)
    {}

    /**
     * Execute the job.
     */
    public function handle(CalculateDiscountService $discountService): void
    {
        $discount = ProductDiscount::where('product_id',$this->product->id)
        ->where('is_active',true)
        ->with('campaign')
        ->first();

        if(!$discount) return;

        $newPrice = $discountService->calculateDiscount(
            $this->product->price,$discount->campaign
        );
        $discount->update(['discount_price'=>$newPrice]);
    }
}
