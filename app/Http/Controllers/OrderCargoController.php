<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Cargo\CreateCargoRequest;

use App\Models\Order;
use App\Models\OrderCargoDetail;
use App\Models\CargoItem;
use Illuminate\Support\Facades\DB;


class OrderCargoController extends Controller
{
    public function createCargo(CreateCargoRequest $request){
        try {
            $data = $request->validated();

            $order = Order::where('id',$data['order_id'])->with('orderItems')->first();
            $orderItems = $order->orderItems->keyBy('id');

            DB::transaction(function() use ($data, $order, $orderItems) {

                $cargo = new OrderCargoDetail;
                $cargo->order_id = $data['order_id'];
                $cargo->cargo_company = $data['cargo_company'];
                $cargo->tracking_code = $data['tracking_code'];
                $cargo->status = 'shipped';
                $cargo->shipped_at = now();
                $cargo->save();

                $itemsToProcess = ($data['all_order_items'] ?? false)
                ? $order->orderItems
                : $data['order_items'];

                $shippedQuantities = CargoItem::whereIn('order_item_id',$orderItems->keys())
                ->selectRaw('order_item_id, SUM(quantity) as total')
                ->groupBy('order_item_id')
                ->pluck('total', 'order_item_id');

                // 185 => 2, yapıp foreachte direkt çekebileceğiz

                
                foreach ($itemsToProcess as $item) {
                    $itemId = is_array($item) ? $item['item_id'] : $item->id;
                    $quantity = is_array($item) ? $item['quantity'] : $item->quantity;

                   
                    throw_unless($orderItems->has($itemId), 
                        \Exception::class, "Bu ürün bu siparişe ait değil: $itemId");

                    $alreadyShipped = $shippedQuantities[$itemId] ?? 0;
                    $remaining = $orderItems[$itemId]->quantity - $alreadyShipped;
                        
                    throw_if($remaining <= 0,
                        \Exception::class, "Bu ürün tamamen kargoya verilmiş: $itemId");

                    throw_if($quantity > $remaining,
                        \Exception::class, "Geçersiz miktar, kalan: $remaining");

            
                    CargoItem::create([
                        'order_cargo_detail_id' => $cargo->id,
                        'order_item_id' => $itemId,
                        'quantity' => $quantity
                    ]);

                }
                $order->status = 'shipped';
                $order->save();

            });
            return response()->json([
                'status' => true,
                'message' => 'Kargo başarıyla oluşturuldu ve ürünler bağlandı.'
            ]);


        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()]);
        }               
    }
}
