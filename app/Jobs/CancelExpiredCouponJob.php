<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\CouponService;
use Illuminate\Support\Facades\DB;
use App\Models\CouponUsage;
use App\Models\Coupon;

class CancelExpiredCouponJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $couponUsageId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(CouponService $couponService): void
    {
        DB::transaction(function() use ($couponService){

            $couponUsage = CouponUsage::where('id',$this->couponUsageId)->first();

            if(!$couponUsage || $couponUsage->status != 'pending') {
                return;
            }

            if($couponUsage->expires_at > now()) return;

            $coupon = Coupon::where('id',$couponUsage->coupon_id)->lockForUpdate()->first();
            if($coupon){
                $couponService->increaseCouponLimit($coupon);

            }

            $couponUsage->status = 'cancelled';
            $couponUsage->save();




        });
    }
}
