<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;
use App\Models\CargoFee;
use App\Models\Order;

use Stripe\Stripe;
use Stripe\PaymentIntent;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function prepareOrder(Request $request){
        try {

            $userId = Auth::user()->id;
            $userCart = Cart::where('user_id',$userId)->where('is_selected',1)->with('product')->get();
            if ($userCart->isEmpty()) {
                return response()->json(['message' => 'Sepetiniz boş.'], 400);
            }

            $updatedCarts = collect();

            foreach ($userCart as $cart){
                $product= $cart->product;
                if (!$product) return $cart;

                $price = ($product->is_discount_active && $product->price != $product->discount_price) ? $product->discount_price : $product->price;
                $maxStock=$product->is_discount_active ? $product->discount_stock : $product->stock;

                if ($maxStock == 0) {
                    $cart->delete();
                    return response()->json([
                        'message' => "{$product->name} ürünü stokta kalmadığı için sepetten kaldırıldı."
                    ], 400);
                }

                if($cart->quantity>$maxStock){
                    $cart->quantity=$maxStock;
                    $cart->total=$cart->quantity*$price;
                    $cart->save();
                    
                    return response()->json(['message'=>"{$product->name} ürününden en fazla {$maxStock} adet alabilirsiniz"],400);
                }


                $cart->price=$price;
                $cart->total = $price*$cart->quantity;
                $cart->save();
                
              

                $updatedCarts->push($cart);
            }
            $cargoFee = CargoFee::where('is_active',1)->first();
            $totalPrice = $updatedCarts->sum('total');
            $subTotal=$totalPrice;
            $appliedCargoFee=0;
            
            if($totalPrice<$cargoFee->free_shipping_threshold)
            { $totalPrice+=$cargoFee->price;
                $appliedCargoFee=$cargoFee->price;
            }


            return response()->json(['data'=>$updatedCarts,'subTotal'=>$subTotal,'cargoFee'=>$cargoFee->price,
            'orderCargoFee'=>$appliedCargoFee,'total'=>$totalPrice],200);

        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);

        }

    }

    public function preparePayment(Request $request){

        try {

            $user = Auth::user();
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
                'users_address_id'=>3,   //$request->selected_address,
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
