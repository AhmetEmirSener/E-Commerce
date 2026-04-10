<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;
use App\Models\CargoFee;
use App\Models\Order;
use App\Models\UserAddress;

use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Http\Resources\CartResource;
use App\Http\Resources\AddressResource;
use Stripe\HttpClient\CurlClient;
use Stripe\ApiRequestor;

use App\Services\CartService;

use App\Services\Iyzico\IyzicoService;

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

    public function preparePayment(Request $request){

        try {

            $user = $request->get('auth_user')->with('address')->first();
            $userAddress = $user->address;
        
            $userCart = Cart::where('user_id',$user->id)->where('is_selected',1)->with('product.advert','product.activeDiscount')->get();
         
            if($userCart->isEmpty()){
                return response()->json(['message'=>'Sepetiniz boş.'],400);
            }

            $total = $userCart->sum('total');

            if ($total <= 0) {
                return response()->json(['message' => 'Geçersiz toplam tutar.'], 400);
            }



            $freshTotal = $this->cartService->updatedCart($userCart)['summary']['total'];
            
            if($total !== $freshTotal){
                return $this->prepareOrder($request);
            }


            $order= Order::create([

                'user_id'=>$user->id,
                'ordered_at'=>now(),
                'users_address_id'=>$user->address->id,
                'total'=>$total,
                'payment_status'=>'pending'

            ]);

            $result = $this->iyzicoService->initializeCheckoutForm([
                'order_id'=>$order->id,
                'total'=>$total,
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

            return response()->json([
                'token'               => $result->getToken(),
                'checkoutFormContent' => $result->getCheckoutFormContent(),
            ]);



        



            return response()->json([
                'order_id'=>$order->id,
                'client_secret'=>$intent->client_secret,
                'total'=>$total,
            ]);



        }catch (\Exception $e) {

            return response()->json(['message'=>$e->getMessage()],500);

        }

    }}
