<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Cart;

class CleanEmptyStockCartsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $productId;

    /**
     * Create a new job instance.
     */
    public function __construct($productId)
    {
        $this->productId=$productId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Cart::where('product_id',$this->productId)->delete();
    }
}
