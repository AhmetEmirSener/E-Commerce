<?php

namespace App\Services;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\CleanEmptyStockCartsJob; 

class StockService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function decreaseStock(Order $order):bool{

        if (!$order->relationLoaded('orderItems')) {
            $order->load('orderItems');
        }
        $sortedItems = $order->orderItems->sortBy('product_id');
        try {
            DB::transaction(function() use ($sortedItems){
                foreach($sortedItems as $item){
                    $product = Product::lockForUpdate()->find($item->product_id);

                    if (!$product) {
                        throw new \Exception("Ürün bulunamadı: {$item->product_id}");
                    }

                    if ($product->stock < $item->quantity) {
                        throw new \Exception("Yetersiz stok: {$product->name}");
                    }

                    $newStock = $product->stock - $item->quantity;

                    $product->decrement('stock',$item->quantity);

                    if($newStock <=0){
                        CleanEmptyStockCartsJob::dispatch($product->id)->afterCommit();
                    }
                }
            });
            return true;

        } catch (\Throwable $th) {
            Log::critical('Stok düşme hatası: ' . $th->getMessage(), [
                'order_id' => $order->id
            ]);
            return false;
        }   
    }

    public function increaseStock(Order $order):bool{
        if (!$order->relationLoaded('orderItems')) {
            $order->load('orderItems');
        }
        $sortedItems = $order->orderItems->sortBy('product_id');

        try {
            DB::transaction(function () use ($sortedItems){
                foreach($sortedItems as $item){
                    $product = Product::lockForUpdate()->find($item->product_id);

                    if (!$product) {
                        throw new \Exception("Ürün bulunamadı: {$item->product_id}");
                    }


                    $product->increment('stock',$item->quantity);


                }

            });

            return true;

        } catch (\Throwable $th) {
            Log::critical('Stok arttırma hatası: ' . $th->getMessage(), [
                'order_id' => $order->id
            ]);
            return false;
        }  
    }
}
