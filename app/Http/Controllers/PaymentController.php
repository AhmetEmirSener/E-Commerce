<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;
use App\Models\CargoFee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SavedCard;

use App\Models\UserAddress;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Http\Resources\CartResource;
use App\Http\Resources\AddressResource;
use Stripe\HttpClient\CurlClient;
use Stripe\ApiRequestor;

use App\Services\CartService;

use App\Services\Iyzico\IyzicoService;
use App\Services\StockService;

use App\Http\Resources\OrderResource;
use App\Http\Requests\CheckCardInfosRequest;
use App\Http\Requests\Payment\CheckTokenPaymentRequest;

use App\Http\Requests\Payment\CheckInstallments;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected CartService $cartService;
    protected IyzicoService $iyzicoService;
    protected StockService $stockService;
    
    public function __construct(CartService $cartService, IyzicoService $iyzicoService,StockService $stockService){
        $this->cartService =$cartService;

        $this->iyzicoService = $iyzicoService;

        $this->stockService = $stockService;



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
            /*
            if($cartService['priceChanged']){
                $returnMessages->push("Bazı ürün veya ürünlerde fiyat değişikliği yaşandı. Sepetinizi kontrol ediniz");
            }
            */

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
            $userSavedCards = SavedCard::where('user_id',$user_id)->select('id','card_alias','last_four','is_default','bin_number','card_type','card_bank')->get();
            $defaultCard=$userSavedCards->where('is_default',1)->first();
            if($defaultCard){
                $result = $this->iyzicoService->getInstallments($defaultCard->bin_number,$updatedCarts['summary']['total']);
                
                if($result->getStatus() !== 'success'){
                    return response()->json(['error'=>$result->getErrorMessage()]);
                }

                $installments=$this->handleInstallments($result,$updatedCarts['summary']['total']);
            }
            $userAdress = UserAddress::where('user_id',$user_id)->where('is_default',1)->first();
            return response()->json([
                'data'=>CartResource::collection($updatedCarts['carts']),
                'summary'=>$updatedCarts['summary'],
                'savedCards'=>$userSavedCards,
                'installments'=>$installments ?? null,
                'address'=>$userAdress ? new AddressResource($userAdress) : null,
                ]);
      
        });
        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);

        }

    }

    public function buildOrder($data, $user){
        try {

            $userAddress = $user->address;

            if(!$userAddress){
                return response()->json(['message'=>'Sipariş vermek için adres eklemelisiniz.'],400);
            }
           

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

            if (round($total, 2) !== round($freshTotal, 2)){
                return response()->json([
                    'status'=>'error',
                    'key'=>'checkout',
                    'action'=>'redirect'
                ]);
                //return $this->prepareOrder($request);
            }

            $cartCargoFee = $freshCart['summary']['cartCargoFee'];
            $paidPrice = $cartCargoFee + $total;

            if(!empty($data['installment']) && $data['installment'] >1){
                $cardNumber = $data['card_number'] ?? null;
                $binNumber = $data['bin_number'] ?? null;

                if (!$cardNumber && !$binNumber) return;


                $bin = $cardNumber ? substr($cardNumber, 0, 6) : $binNumber;

                $paidPrice = $this->iyzicoService->getPaidPrice($bin,$paidPrice,$data['installment']);
            }
            
            DB::beginTransaction();

            try {
                $order= Order::create([

                    'user_id'=>$user->id,
                    'ordered_at'=>now(),
                    'users_address_id'=>$user->address->id,
                    'total'=>$paidPrice,
                    'cargo_fee'=>$cartCargoFee,
                    'payment_status'=>'pending',
                    'save_card'=>$data['save_card'] ?? 0
    
                ]);

                $orderItems=[];
                foreach($userCart as $item){
                    $orderItems[]=[
                        'order_id'   => $order->id,
                        'product_id' => $item->product_id,
                        'quantity'   => $item->quantity,
                        'price'      => $item->price,
                        'total'      => $item->total,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                OrderItem::insert($orderItems);

                DB::commit();

                return [
                    'order'      => $order,
                    'userCart'   => $userCart,
                    'order_id'   => $order->id,
                    'total'      => $total,
                    'paidPrice'  => $paidPrice,
                    'installment'=> $data['installment'] ?? 1,
                    'save_card'  => $data['save_card'] ?? 0,
                    'user'       => [
                        'id'      => $user->id,
                        'name'    => $user->name,
                        'surname' => $user->surname,
                        'email'   => $user->email,
                        'address' => 'Türkiye',
                        'city'    => $user->address->city,
                    ],
                    'items' => $userCart,
                    
                ];
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => 'Sipariş oluşturulurken bir hata oluştu: ' . $th->getMessage()], 500);
        }




        }catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }

    }

    public function payWithCard(CheckCardInfosRequest $request){
        $data = $request->validated();
        $user = $request->get('auth_user')->load('address');
        $orderData = $this->buildOrder($data,$user);
        if ($orderData instanceof JsonResponse) return $orderData; 

        $result = $this->iyzicoService->initialize3DS([
            ...$orderData,
            'card'=>[
                'holder_name'=>$data['card_holder_name'],
                'number'=>$data['card_number'] ,
                'expire_month'=>$data['expire_month'],
                'expire_year'=>$data['expire_year'],
                'cvc'=>$data['cvc'],
            ],
            'ip'=> $request->ip(),

        ]);
        return $this->handleIyzicoResult($result);

    }

    public function payWithSavedCard(CheckTokenPaymentRequest $request){
        $data = $request->validated();
        $user = $request->get('auth_user')->load('address');
        $savedCard = SavedCard::where('user_id',$user->id)->where('id',$data['saved_card_id'])->first();

        if(!$savedCard){
            return response()->json(['message'=>'Kayıtlı kart bulunamadı.'],404);
        }   
        $data['bin_number'] = $savedCard->bin_number ?? 0;
        $orderData = $this->buildOrder($data,$user);

        if ($orderData instanceof JsonResponse) return $orderData; 

        $result = $this->iyzicoService->initialize3DSWithToken([
            ...$orderData,
            'card_token'=>$savedCard->card_token,
            'card_user_key'=>$savedCard->card_user_key,
            'ip'=> $request->ip(),
        ]);

        return $this->handleIyzicoResult($result);

    }

    public function handleIyzicoResult($result){
        if($result->getStatus() !== 'success'){
            return response()->json([
                'message'=>$result->getErrorMessage()
            ],400);
        }

        return response()->json([
            'html_content'=>$result->getHtmlContent()
        ]);
    }






    public function callback(Request $request){

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
        $this->stockService->decreaseStock($order);

        if($result->getCardToken() && $result->getCardUserKey() && $order->save_card){
            SavedCard::updateOrCreate(
                [
                    'user_id'=>$order->user_id,
                    'card_token'=>$result->getCardToken(),
                ],
                [
                    'card_user_key'=>$result->getCardUserKey(),
                    'card_alias'=>'Kart ' . $result->getLastFourDigits(),
                    'card_bank' => $result->getCardAssociation() ?? '',
                    'card_family' => $result->getCardFamily() ?? '',
                    'card_type' => $result->getCardType() ?? '',
                    'bin_number' => $result->getBinNumber() ?? '',
                    'last_four'=>$result->getLastFourDigits() ?? '',
                    'is_default'=>!SavedCard::where('user_id',$order->user_id)->exists()
                ]
                );
        }

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


    public function getInstallments(CheckInstallments $request){
        try {
            $data = $request->validated();
            $user = $request->get('auth_user');
            $cartItems = $user->cartItems()->with('product.activeDiscount')->get();

            if($cartItems->isEmpty()) return response()->json(['message'=>'Sepetiniz boş.'],400);

            $subTotal = $cartItems->sum('total');
            $total = $this->cartService->calculateTotalwCargoFee($subTotal);

            $result = $this->iyzicoService->getInstallments($data['card_number'],$total);

            if($result->getStatus() !== 'success'){
                return response()->json(['error'=>$result->getErrorMessage()]);
            }

            $installments=$this->handleInstallments($result,$total);
            

            return response()->json([
                'installments'=>$installments,
                'card_type'=>$result->getInstallmentDetails()[0]->getCardType() ?? null,
                'card_family'=>$result->getInstallmentDetails()[0]->getCardFamilyName() ?? null
            ]);
        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()],500);
        }
    }



    public function handleInstallments($result,float $total){
        $installments=[];

        foreach($result->getInstallmentDetails() as $detail){
            foreach($detail->getInstallmentPrices() as $installmentPrice){
                $installments[]=[
                    'installment'=> $installmentPrice->getInstallmentNumber(),
                    'installment_price'=>$installmentPrice->getInstallmentPrice(),
                    'installment_diff'=>round($installmentPrice->getTotalPrice() - $total,2),
                    'total_price'=>$installmentPrice->getTotalPrice()
                ];
            }
        }

        return $installments;
    }


}
