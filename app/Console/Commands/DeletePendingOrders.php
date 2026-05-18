<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;

class DeletePendingOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-pending-orders';

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
        Order::where('status','pending')
        ->where('created_at','<=',now()->subDays(30))
        ->delete();

    }
}
