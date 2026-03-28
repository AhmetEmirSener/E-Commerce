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

use App\Services\CartService;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService){
        $this->cartService =$cartService;
    }

    public function prepareOrder(Request $request){
        try {
            return DB::transaction(function () use ($request){

          
            $user_id = $request->get('auth_user')->id;
            $carts = Cart::where('user_id',$user_id)->where('is_selected',1)->lockForUpdate()->with('product.advert','product.activeDiscount')->get();
            
            if ($carts->isEmpty()) {
                return response()->json(['message' => 'Sepette seçili ürün yok.'], 400);
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
            
            if($returnMessages->isNotEmpty()){
                return response()->json([
                    'status'=>'error',
                    'action'=>'redirect',
                    'key'=>'cart',
                    'errors' => (object)$returnMessages->all(),
                ],400);
            }

            $freshCarts = Cart::where('user_id',$user_id)->where('is_selected',1)->with('product.advert','product.activeDiscount')->get();

            $updatedCarts = $this->cartService->updatedCart($freshCarts);
        
            $userAdress = UserAddress::where('user_id',$user_id)->where('is_default',1)->first();

            return response()->json([
                'data'=>CartResource::collection($updatedCarts['carts']),
                'summary'=>$updatedCarts['summary'],
                'message'=>(object)$returnMessages,
                'address'=>new AddressResource($userAdress)
                ]);
      
        });
        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);

        }

    }

    public function preparePayment(Request $request){

        try {

            $user = $request->get('auth_user');
            $userCart = Cart::where('user_id',$user->id)->where('is_selected',1)->get();

            if($userCart->isEmpty()){
                return response()->json(['message'=>'Sepetiniz boş.'],400);
            }

            $total = $userCart->sum('total');
            
            if ($total <= 0) {
                return response()->json(['message' => 'Geçersiz toplam tutar.'], 400);
            }

            $order= Order::create([

                'user_id'=>$user->id,
                'ordered_at'=>now(),
                'users_address_id'=>4,   //$request->selected_address,
                'total'=>$total,
                'payment_status'=>'pending'

            ]);



            Stripe::setApiKey(env('STRIPE_SECRET'));
            $intent= PaymentIntent::create([
                'amount'=>intval(round($total*100)),
                'currency'=>'try',
                'metadata'=>['order_id'=>$order->id],
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
