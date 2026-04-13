<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;
use App\Models\CargoFee;
use App\Models\Order;
use App\Models\OrderItem;

use App\Models\UserAddress;
use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Http\Resources\CartResource;
use App\Http\Resources\AddressResource;
use Stripe\HttpClient\CurlClient;
use Stripe\ApiRequestor;

use App\Services\CartService;

use App\Services\Iyzico\IyzicoService;
use App\Http\Resources\OrderResource;
use App\Http\Requests\CheckCardInfosRequest;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected CartService $cartService;
    protected IyzicoService $iyzicoService;

    public function __construct(CartService $cartService, IyzicoService $iyzicoService){
        $this->cartService =$cartService;

        $this->iyzicoService = $iyzicoService;


    }

    public function prepareOrder(Request $request){
        try {
            return DB::transaction(function () use ($request){

          
            $user_id = $request->get('auth_user')->id;
            $carts = Cart::where('user_id',$user_id)->where('is_selected',1)->lockForUpdate()->with('product.advert','product.activeDiscount')->get();
            
            if ($carts->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'action'=>'redirect',
                    'key'=>'cart',
                    'errors' => ['Sepette seçili ürün yok.'],
                    'message'=>'Sepette seçili ürün yok.'
            ], 400);
            }

            $cartService = $this->cartService->updatedCart($carts);
            $returnMessages=collect();
            foreach($cartService['carts'] as $cart){

                $product = $cart->product;
                if(!$product) continue;

                $maxStock = $product->stock;

                if($maxStock==0){
                    $cart->delete();
                    $returnMessages->push("{$product->name} stoklarda kalmadığı için sepetten kaldırıldı");
                    continue;
                }
                if($cart->quantity>$maxStock){
                    $cart->quantity = $maxStock;
                    $returnMessages->push( "{$product->name} stok miktarı kadar güncellendi"); 
                }
                
                $cart->save();
            }
            if($cartService['priceChanged']){
                $returnMessages->push("Bazı ürün veya ürünlerde fiyat değişikliği yaşandı. Sepetinizi kontrol ediniz");
            }
  
            if($returnMessages->isNotEmpty()){
                return response()->json([
                    'status'=>'error',
                    'action'=>'redirect',
                    'key'=>'cart',
                    'errors' => $returnMessages->values()->all(),
                    'message'=>'Sepet Güncellendi'
                ],400);
            }
            

            $freshCarts = Cart::where('user_id',$user_id)->lockForUpdate()->where('is_selected',1)->with('product.advert','product.activeDiscount')->get();

            $updatedCarts = $this->cartService->updatedCart($freshCarts);
        
            $userAdress = UserAddress::where('user_id',$user_id)->where('is_default',1)->first();
            return response()->json([
                'data'=>CartResource::collection($updatedCarts['carts']),
                'summary'=>$updatedCarts['summary'],
                'address'=>$userAdress ? new AddressResource($userAdress) : null,
                ]);
      
        });
        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);

        }

    }

    public function preparePayment(CheckCardInfosRequest $request){
        try {
            $data = $request->validated();

            $user = $request->get('auth_user')->with('address')->first();
            $userAddress = $user->address;
        
            $userCart = Cart::where('user_id',$user->id)->where('is_selected',1)->with('product.advert','product.activeDiscount')->get();
         
            if($userCart->isEmpty()){
                return response()->json(
                    [
                        'status'=>'error',
                        'message'=>'Sepetiniz boş.',
                        'key'=>'cart',
                        'action'=>'redirect',
                    ]
                ,400);
            }

            $total = $userCart->sum('total');

            if ($total <= 0) {
                return response()->json(['message' => 'Geçersiz toplam tutar.'], 400);
            }


            $freshCart = $this->cartService->updatedCart($userCart);
            $freshTotal = $freshCart['summary']['subTotal'];

            if($total !== $freshTotal){
                return $this->prepareOrder($request);
            }

            $cartCargoFee = $freshCart['summary']['cartCargoFee'];
            $paidPrice = $cartCargoFee + $total;

            $order= Order::create([

                'user_id'=>$user->id,
                'ordered_at'=>now(),
                'users_address_id'=>$user->address->id,
                'total'=>$paidPrice,
                'cargo_fee'=>$cartCargoFee,
                'payment_status'=>'pending'

            ]);

     
            
            
            $result = $this->iyzicoService->initialize3DS([
                'order_id'=>$order->id,
                'total'=>$total,
                'paidPrice'=>$paidPrice,
                'save_card'=>$data['save_card'] ?? 0,
                'card_user_key'=>$user->iyzico_card_user_key ?? null,
                'card'=>[
                    'holder_name'=>$data['card_holder_name'],
                    'number'=>$data['card_number'] ,
                    'expire_month'=>$data['expire_month'],
                    'expire_year'=>$data['expire_year'],
                    'cvc'=>$data['cvc']
                ],
                'user'=>[
                    'id'=>$user->id,
                    'name'=>$user->name,
                    'surname'=>$user->surname,
                    'email'=>$user->email,
                    'address'=>'Türkiye',
                    'city'=>$user->address->city,


                ],
                'items'=>$userCart,
                'ip'=>$request->ip(),
            ]);


            if($result->getStatus() !== 'success'){
                return response()->json([
                    'message'=>$result->getErrorMessage()
                ],400);
            }

            foreach($userCart as $items){
                OrderItem::create([
                    'order_id'=>$order->id,
                    'product_id'=>$items->product_id,
                    'quantity'=>$items->quantity,
                    'price'=>$items->price,
                    'total'=>$items->total
                ]);
            }


     

            return response()->json([
                'html_content'=>$result->getHtmlContent()
            ]);

            return response()->json([
                'token'               => $result->getToken(),
                'checkoutFormContent' => $result->getCheckoutFormContent(),
            ]);
 


        }catch (\Exception $e) {

            return response()->json(['message'=>$e->getMessage()],500);

        }

    }

    public function callback(Request $request ){

        $status = $request->status;
        $mdStatus = $request->mdStatus;
        $paymentId = $request->paymentId;
        $conversationId = $request->conversationId;

        if ($status !== 'success' || $mdStatus != 1) {
            return redirect('http://localhost:4200/payment/result?status=failed');
        }

        $result = $this->iyzicoService->complete3DS($paymentId, $conversationId);
       
        if ($result->getStatus() !== 'success') {
            return redirect('http://localhost:4200/payment/result?status=failed');
        }

        $order = Order::where('id', $conversationId)->first();
        $order->update([
            'payment_status'        => 'completed',
            'payment_id'    => $result->getPaymentId(),
        ]);

        $order->user->cartItems()->delete();

        $resultToken = \Str::random(64);

        cache()->put('payment_result_' . $resultToken, [
            'status'=>'success',
            'order_id'=>$order->id
        ], now()->addMinutes(60));


        return redirect('http://localhost:4200/payment/result?token=' . $resultToken);

        /*
        if ($result->getCardToken() && $result->getCardUserKey()) {
            $order->user->update([
                'iyzico_card_token'    => $result->getCardToken(),
                'iyzico_card_user_key' => $result->getCardUserKey(),
            ]);
        }
            */
    }



    public function paymentResult(Request $request, $token){
        $data = cache()->get('payment_result_'.$token);
                
        if(!$data){
            return response()->json(['message' => 'Sayfa bulunamadı'], 404);
        }   

        $userId = $request->get('auth_user')->id;

        $order = Order::where('id',$data['order_id'])->with('orderItems.product')->first();
        
        if($order->user_id !== $userId){
            return response()->json(['message' => 'Sayfa bulunamadı'], 403);
        }

        return new OrderResource($order);

        return response()->json($order);


        return response()->json('SALAMLAR');
        
    }


}
