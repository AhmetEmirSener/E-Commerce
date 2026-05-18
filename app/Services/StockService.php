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
                        throw new \Exception("Yetersiz stok: {$product->id} - {$product->name}  (Mevcut: {$product->stock}, İstenen: {$item->quantity})");
                    }

                    $newStock = $product->stock - $item->quantity;

                    $product->decrement('stock',$item->quantity);

                    if($newStock <=0){
                        CleanEmptyStockCartsJob::dispatch($product->id)->afterCommit();
                    }
                }
            });
            Log::channel('stock')->info('Stok düşüldü', [
                'order_id' => $order->id,
                'items' => $sortedItems->map(fn($item) => [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ])->toArray()
            ]);

            return true;

        } catch (\Throwable $th) {
            Log::channel('stock')->info('Stok düşme hatası: '. $th->getMessage(),[
                'order_id'=>$order->id,
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

            Log::channel('stock')->info('Stok artırıldı', [
                'order_id' => $order->id,
            ]);
            
            return true;

        } catch (\Throwable $th) {
            Log::channel('stock')->info('Stok arttırma hatası: '. $th->getMessage(),[
                'order_id'=>$order->id,
            ]); 
        
            return false;
        }  
    }
}
