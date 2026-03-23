<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Advert;
use App\Models\CargoFee;

use App\Http\Resources\CartResource;

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
            $user_id = $request->auth_user->id;
            
            //$product = Product::findOrFail($request->product_id);

            //$cart = Cart::where('user_id',Auth::user()->id)->where('product_id',$product->id)->first();
            
            $productPrice = $advert->product->activeDiscount ? $advert->product->activeDiscount->discount_price : $advert->product->price;
            
            //return response()->json(['advert'=>$advert,'cart'=>$cart]);

            $maxStock = $advert->product->stock;
            $quantityToAdd=$request->quantity??1;

            return DB::transaction(function () use ($advert,$productPrice, $maxStock,$quantityToAdd,$user_id){

            $cart = Cart::where('user_id',$user_id)->where('advert_id',$advert->id)
            ->lockForUpdate()->first();

            if($cart){

                if($cart->quantity>=$maxStock){
                    return response()->json([
                        "message" => "Alabileceğiniz en fazla ürün miktarı sepetinizde mevcut."
                    ], 400);
                }
                
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
                'user_id'=>$user_id,
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

            $user_id = $request->auth_user->id;

            return DB::transaction(function () use ($advert,$request,$user_id){
            $cart = Cart::where('user_id',$user_id)->where('advert_id',$advert->id)
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

    public function getUsersCart(Request $request){
        try {
            $carts = Cart::where('user_id',1)->with('product.advert','product.activeDiscount')->get();
            //return response()->json(['cart'=>$carts]);

            if($carts->isEmpty()){
                return response()->json([
                    'data' => [],
                    'summary' => [
                        'count'      => 0,
                        'cartCount'=>0,
                        'subTotal'   => 0,
                        'cargoFee'   => 0,
                        'cargoCartFee' => 0,
                        'total'      => 0
                    ]
                ], 200);
            }
          

            $productCount=0;

            $cartTotal=0;

            $originalTotal=0;

            $cargoData =CargoFee::where('is_active',1)->first(); // cache ekle
            $cargoFee = 0;

            $updatedCarts=$carts->map(function ($cart) use(&$productCount,&$cartTotal,&$originalTotal)
            {


                $product=$cart->product;
                $discount = $product->activeDiscount;

                $productPrice = $product->activeDiscount ? $product->activeDiscount->discount_price : $product->price;

                $cart->price =$productPrice;
                $cart->total = $productPrice * $cart->quantity;

                if($cart->is_selected){
                    $productCount+=$cart->quantity;
     
                    $cartTotal+=$cart->total;
                    $originalTotal+= $product->price * $cart->quantity;
                }
                
        
     
                return $cart;

            });
            $cartCount =$updatedCarts->count();
            $noneSelected = $carts->where('is_selected',1)->isEmpty();

            if($cargoData &&!$noneSelected && $cartTotal<$cargoData->free_shipping_threshold){
                $cargoFee = $cargoData->price;   
            }

            if($noneSelected){
                $cargoFee = 0;
            }
  
            $total = $cartTotal+$cargoFee;

           // $total = $cartTotal+$cargoFee;

            return response()->json([
                'data'=>CartResource::collection($updatedCarts),
                'summary'=>[
                    'count'=>$productCount,
                    'cartCount'=>$cartCount,
                    'subTotal'=>$cartTotal,
                    'cargoFee'=>$cargoData?->price ?? 0,
                    'cargoCartFee'=>$cargoFee,

                    'originalTotal'=> $originalTotal,
                    'discountTotal' => $originalTotal > $cartTotal ? $originalTotal - $cartTotal : 0,
                    'total'=>$total
                    
                ],
              
            ]);
            

            // return response()->json(['data'=>$updatedCarts],200);

        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }

    public function changeSelected(Request $request){
        try {
            $request->validate([
                'advert_slug' => 'required',
            ]);
            $user_id = $request->auth_user->id;
            $advert = Advert::where('slug',$request->advert_slug)->first();

            if(!$advert) return response()->json(['message'=>'Ürün bulunamadı']);
            $cart = Cart::where('user_id',$user_id)->where('advert_id',$advert->id)->first();    

            if(!$cart) return response()->json(['message'=>'Sepetteki ürün bulunamadı']);
            $cart->is_selected =!$cart->is_selected;
            $cart->save();

            return response()->json(['message'=>'Sepet güncellendi']);
        } catch (\Throwable $th) {
            return response()->json([$th->getMessage()],500);
        }
    }
}
