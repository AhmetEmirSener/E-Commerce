<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CouponUsage;
use App\Jobs\CancelExpiredCouponJob;

class ClearExpiredCoupons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-expired-coupons';

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
        CouponUsage::where('expires_at','<',now())->where('status','pending')->pluck('id')->chunk(100)
        ->each(function ($chunkIds){
            foreach ($chunkIds as $id){
                CancelExpiredCouponJob::dispatch($id)->onQueue('coupons');
            }
        });

        $this->info('Süresi dolan kuponlar temizlik kuyruğuna gönderildi.');
    }
}
