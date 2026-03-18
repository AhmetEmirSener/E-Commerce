<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Advert;

use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;


class CartController extends Controller
{

    public function storeCart(Request $request){
        try {
            $request->validate([
                'advert_slug' => 'required',
                'quantity' => 'nullable|integer|min:1'
            ]);
            $advert = Advert::where('slug',$request->advert_slug)->with('product.activeDiscount')->first();
            if(!$advert) return response()->json(['message'=>'Ürün eklenemedi'],404);
            if(!$advert->product) return response()->json(['message'=>'Ürün verisi eksik'],422);

            //$product = Product::findOrFail($request->product_id);

            //$cart = Cart::where('user_id',Auth::user()->id)->where('product_id',$product->id)->first();
            
            $productPrice = $advert->product->activeDiscount ? $advert->product->activeDiscount->discount_price : $advert->product->price;
            
            //return response()->json(['advert'=>$advert,'cart'=>$cart]);

            $maxStock = $advert->product->stock;
            $quantityToAdd=$request->quantity??1;

            return DB::transaction(function () use ($advert,$productPrice, $maxStock,$quantityToAdd){

            $cart = Cart::where('user_id',1)->where('advert_id',$advert->id)
            ->lockForUpdate()->first();

            if($cart){
              
                $cart->quantity+=$quantityToAdd;
                
                if($cart->quantity>$maxStock){
                    $cart->quantity=$maxStock;
                    $cart->total =$cart->quantity*$productPrice;
                    $cart->price =$productPrice;
                    $cart->save();
                    return response()->json([
                        "message" => "Bu üründen en fazla {$maxStock} adet ekleyebilirsiniz."
                    ], 400);
                }
                $cart->total =$productPrice*$cart->quantity;
                $cart->price =$productPrice;
                $cart->save();
                return response()->json(['message'=>'Sepet güncellendi.'],200);

            }
            if($quantityToAdd>$maxStock){
                return response()->json([
                    "message" => "Bu üründen en fazla {$maxStock} adet ekleyebilirsiniz."
                ], 400);
            }

            
            $cartData=[
                'user_id'=>1,
                'advert_id'=>$advert->id,
                'product_id'=>$advert->product->id,
                'price'=>$productPrice,
                'quantity'=>$quantityToAdd,
                'total'=>$productPrice*$quantityToAdd,
            ];

            Cart::create($cartData);
            return response()->json(['message'=>'Ürün sepete eklendi.'],200);
            
            });

        } catch (\Exception $e) {
            return response()->json([$e->getMessage()],500);
}
    }

    public function deleteCart(Request $request){
        try {
            $request->validate([
                'advert_slug' => 'required',
                'delete_all'=>'nullable|boolean'
            ]);
            
            $advert = Advert::where('slug',$request->advert_slug)->with('product')->first();

            if(!$advert) return response()->json(['message'=>'Ürün bulunamadı'],404);   
            if(!$advert->product) return response()->json(['message'=>'Ürün verisi eksik'],422);


            return DB::transaction(function () use ($advert,$request){
            $cart = Cart::where('user_id',1)->where('advert_id',$advert->id)
            ->lockForUpdate()->first();
            if(!$cart){
                return response()->json(['message'=>'Ürün sepette bulunamadı.'],404);
            }
            if($request->delete_all){
                $cart->delete();
                return response()->json(['message'=>'Ürün sepetten kaldırıldı'],200);
            }
            

            $cart->quantity--;
            if($cart->quantity<=0){
                $cart->delete();
                return response()->json(['message'=>'Ürün sepetten kaldırıldı'],200);
            }

            $cart->total = $cart->price*$cart->quantity;
            $cart->save();

            return response()->json(['message'=>'Sepet güncellendi'],200);
            });




        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }

    public function getUsersCart(){
        try {
            $user_id = 1; //Auth::user()->id;
            $carts = Cart::where('user_id',$user_id)->with('product.activeDiscount')->get();
            //return response()->json(['cart'=>$carts]);
            
            if($carts->isEmpty()){
                return response()->json(['message'=>'Sepet boş.'],200);
            }
        

            $updatedCarts=$carts->map(function ($cart)
            {


                $product=$cart->product;
                $productPrice = $product->activeDiscount ? $product->activeDiscount->discount_price : $product->price;

                $cart->price =$productPrice;
                $cart->total = $productPrice * $cart->quantity;

                return $cart;

            });

            return response()->json(['data'=>$updatedCarts],200);

        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }
}
