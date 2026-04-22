<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Refund;
use App\Models\RefundItem;
use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\RefundRequestItem;



use Illuminate\Http\Request;
use App\Http\Resources\OrderResource;
use App\Http\Resources\AddressResource;
use App\Services\Iyzico\IyzicoService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\RefundOrderRequest;

use App\Http\Resources\OrderWithPaymentDetails;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected IyzicoService $iyzicoService;
    protected StockService $stockService;

    public function __construct(IyzicoService $iyzicoService,StockService $stockService){
        $this->iyzicoService = $iyzicoService;
        $this->stockService = $stockService;
    }

    public function orders(Request $request){
        try {
            $user = $request->get('auth_user');    
            $allowedSorts = ['pending','completed','shipped','cancelled'];

            $orders = Order::query()
            ->where('user_id',$user->id)->with('payment','orderItems.product')->withCount('orderItems')
            ->when(in_array($request->status,$allowedSorts), function ($query) use ($request){
                $query->where('status',$request->status);
            })->latest()->paginate(7);
           
            return response()->json([
                'data'=>OrderResource::collection($orders),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'total' => $orders->total(),
                    ]
            ]);

        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()],500);
        }
    }

    public function order(Request $request,$id){
        try {
            $user = $request->get('auth_user');    
            $order = Order::where('id',$id)->with('payment','orderItems.product')
            ->withCount('refundRequest')
            ->withCount('orderItems')->first();
            
            if(!$order){
                return response()->json(['message'=>'Sipariş bulunamadı'],404);
            }
            if($order->user_id !== $user->id){
                return response()->json(['message','Sipariş bulunamadı'],404);
            }
           
            return response()->json([
                'data'=>new OrderWithPaymentDetails($order),
            ]);

        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()],500);
        }
    }


    public function cancelOrder(Request $request,$orderId){
        try {
            $user = $request->get('auth_user');

            $order = Order::where('id',$orderId)->with('payment','orderItems')->first();

            if(!$order || $order->user_id !== $user->id){
                return response()->json(['message'=>'Sipariş bulunamadı'],404);
            }
            if($order->status == 'cancelled' ){
                return response()->json(['message'=>'Sipariş zaten iptal edildi.'],400);
            }
            if($order->status == 'completed' ){
                return response()->json(['message'=>'Teslim edilmiş ürünler iptal edilemez, lütfen iade isteği oluşturunuz.'],400);

            }
            if( $order->status == 'shipped'){
                return response()->json(['message'=>'Kargoya verilmiş ürünler iptal edilemez, lütfen teslim aldıktan sonra iade oluşturunuz.'],400);
            }
            
            if (!$order->payment || $order->payment->status !== 'paid'){
                return response()->json(['message'=>'Sipariş ödemesi yok.'],400);
            }

            $cancelResult = $this->iyzicoService->cancelPayment($order->payment->provider_payment_id,$request->ip());
            if ($cancelResult->getStatus() !== 'success') {
                return response()->json(['message' => 'İptal işlemi banka tarafından reddedildi.'],400);
            }

            try {
                DB::transaction(function() use ($order){
              
    
                    $stockReturn = $this->stockService->increaseStock($order);
                    if (!$stockReturn) {
                        throw new \Exception('Stok güncellenemedi');
                    }
                    $order->status = 'cancelled';
                    $order->payment->status = 'cancelled';
                    $order->save();
                    $order->payment->save();
                });
                return response()->json(['message'=>'İptal işlemi başarılı. Bankanıza bağlı olarak 1-7 iş
                     günü içerisinde iade işlemi tamamlanacaktır.']);   
            } catch (\Exception $e) {

                Log::critical('Iyzico iptali başarılı ancak DB güncellenemedi!', [
                    'order_id' => $orderId,
                    'error_message' => $e->getMessage()
                ]);

                return response()->json(['message' => 'İptal işlemi sağlandı ancak sistem güncellenirken hata oluştu.'], 500);
            }
            

        } catch (\Throwable $th) {
            Log::error('Cancel Order General Error: ' . $th->getMessage(), ['order_id' => $orderId]);
            return response()->json(['error' => 'İşlem sırasında beklenmeyen hata oluştu.'], 500);        }
    }


    // REFUND REQUEST İLE BİRLEŞTİR HAZIR DEĞİL
    public function refundOrder(RefundOrderRequest $request,$orderId){
        try {
            $data = $request->validated();
            $user = $request->get('auth_user');

            $order = Order::where('id',$orderId)->with('payment','orderItems')->first();

            if($order->user_id !== $user->id){
                return response()->json(['message'=>'Sipariş bulunamadı']);
            };
            if(!in_array($order->status, ['completed', 'shipped'])){
                return response()->json(['message' => 'Bu sipariş iade edilemez.'], 400);
            }

     
            $orderItems=collect();
            $refundAmount = 0;
            if($data['all_order'] ?? false){
                $existingRefund = Refund::where('order_id', $order->id)->exists();
                if($existingRefund){
                    return response()->json(['message' => 'Bu siparişin daha önce iade işlemi yapılmış.'], 400);
                }
                $refundAmount = $order->payment->amount;
                $orderItems = $order->orderItems->map(function($item){
                    $item->refundAmount = $item->price * $item->quantity;
                    return $item;
                });
            } else {

                foreach($data['order_items'] as $item){
                    $orderItem = $order->orderItems->where('id',$item['item_id'])->first();  
                    if(!$orderItem) continue;



                    if($orderItem->quantity < $item['quantity']){
                        $item['quantity']=$orderItem->quantity;
                    }
    
                    $alreadyRefunded = RefundItem::where('order_item_id', $orderItem->id)->sum('quantity');
                    $availableQuantity = $orderItem->quantity - $alreadyRefunded;
    
                    if($availableQuantity <= 0){
                        continue; // ya da hata dön
                    }
                    if($item['quantity'] > $availableQuantity){
                        $item['quantity'] = $availableQuantity;
                    }
    
                    $orderItem->quantity = $item['quantity'];
    
                    $refundItemAmount = $orderItem->price * $item['quantity'];
                    $refundAmount += $refundItemAmount;
                    $orderItem->refundAmount = $refundItemAmount;
                    $orderItems->push($orderItem);
    
                }

         
            }

            if($orderItems->isEmpty()){
                return response()->json(['message' => 'İade edilebilir ürün bulunamadı.'], 400);
            }

            $refundResults = [];
            foreach($orderItems as $orderItem){
                $refundResult = $this->iyzicoService->refundPayment(
                    $orderItem->payment_transaction_id,
                    $orderItem->refundAmount,
                    $request->ip()
                );
                if($refundResult->getStatus() !== 'success'){
                    return response()->json(['message' => 'İade işlemi gerçekleştirilemedi.'], 500);
                }
                $refundResults[] = [
                    'orderItem' => $orderItem,
                    'refundId'  => $refundResult->getPaymentTransactionId()
                ];
            }


                DB::transaction(function() use ($refundAmount,$orderItems,$order,$user,$refundResults){
                    $refund= new Refund;
                    $refund->order_id = $order->id;
                    $refund->user_id = $user->id;
                    $refund->provider_refund_id = collect($refundResults)->pluck('refundId')->implode(',');
                    $refund->amount= $refundAmount;
                    $refund->status = 'success';
                    $refund->save();

                    foreach($orderItems as $item){
                        $refundItems= new RefundItem;
                        $refundItems->refund_id = $refund->id;
                        $refundItems->order_item_id=$item->id;
                        $refundItems->quantity = $item->quantity;
                        $refundItems->amount = $item->refundAmount;
                        $refundItems->save();
                    
                    }   
                    /*
                    foreach($orderItems as $item){
                        $product = Product::lockForUpdate()->find($item->product_id);
                        $product->increment('stock', $item->quantity);
                    }
                    */

                });
                return response()->json(['message' => 'İade işlemi başarılı.']);



        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()]);
        }
    }


    public function orderRefundInfo(Request $request,$orderId){
        try {
            $user = $request->get('auth_user');
            $order = Order::where('id',$orderId)->with('orderItems','refundRequest.refundRequestItem')->first();
            
            if(!$order || $order->user_id !== $user->id){
                return response()->json(['message'=>'Sipariş bulunamadı'],404);
            }
            $items = $order->orderItems->map(function($item) {
                $usedQty = RefundRequestItem::where('order_item_id', $item->id)
                    ->whereHas('refundRequest', fn($q) =>
                        $q->whereNotIn('status', ['rejected'])
                    )->sum('quantity');
    
                return [
                    'id'                 => $item->id,
                    'name'               => $item->product->name,
                    'image'              => $item->product->image ?  asset('storage/' . $item->product->image) : null,
                    'quantity'           => $item->quantity,
                    'available_quantity' => $item->quantity - $usedQty,
                    'price'              => $item->price,
                    'total'              =>$item->price * $item->quantity - $usedQty
                ];
            });

            $items = $items->filter(fn($i) => $i['available_quantity'] > 0)->values();
            return response()->json([
                
                'data'    => $items

            ]);
            

        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()],500);

        }
    }


}
