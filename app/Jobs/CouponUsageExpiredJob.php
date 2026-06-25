<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\CouponService;

class CouponUsageExpiredJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(Order $order, CouponService $couponService): void
    {
       DB::transaction(function () use ($order,$couponService){
        $couponUsage = CouponUsage::where('order_id',$order->id)->first();
        if(!$couponUsage){
            return;
        }
        $usageStatus = $couponUsage->status;
        if($usageStatus== 'completed') return;
        $coupon = Coupon::where('id',$couponUsage->coupon_id)->lockForUpdate()->first();

        $couponLimit = $coupon->usage_limit;
        if($couponLimit>0){

            if($usageStatus === 'cancelled'){
                $couponService->decreaseCouponLimit($coupon);
                $couponUsage->status = 'completed';
            }



        }else{
            if($usageStatus === 'cancelled'){
                Log::channel('order')->error('Sipariş alındı, kupon rezerve aktif değil, kupon stok yok!',[
                    'order_id'=>$order->id,
                ]);
                return;
            }
        }
       

        if($usageStatus == 'pending'){  // rezerve duruyor, complete çek bitir
            $couponUsage->status = 'completed';
        }
    
        $couponUsage->save();

       });
    }
}
