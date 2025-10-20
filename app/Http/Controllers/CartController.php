<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;


class CartController extends Controller
{
    public function storeCart(Request $request){
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'nullable|integer|min:1'
            ]);

            $product = Product::findOrFail($request->product_id);
            
            $cart = Cart::where('user_id',Auth::user()->id)->where('product_id',$product->id)->first();

            $price = $product->is_discount_active ? $product->discount_price : $product->price;
            $maxStock = $product->is_discount_active ? $product->discount_stock : $product->stock;
            $quantityToAdd=$request->quantity??1;

            if($cart){
                $cart->quantity+=$quantityToAdd;
                
                if($cart->quantity>$maxStock){
                    $cart->quantity=$maxStock;
                    $cart->total =$cart->quantity*$price;
                    $cart->save();
                    return response()->json([
                        "message" => "Bu üründen en fazla {$maxStock} adet ekleyebilirsiniz."
                    ], 400);
                }
                $cart->total =$price*$cart->quantity;
                $cart->price =$price;
                $cart->save();
                return response()->json(['message'=>'Sepet güncellendi.'],200);

            }
            if($quantityToAdd>$maxStock){
                return response()->json([
                    "message" => "Bu üründen en fazla {$maxStock} adet ekleyebilirsiniz."
                ], 400);
            }
            $cartData=[
                'user_id'=>Auth::user()->id,
                'product_id'=>$product->id,
                'price'=>$price,
                'quantity'=>$quantityToAdd,
                'total'=>$price*$quantityToAdd,
            ];
            Cart::create($cartData);
            return response()->json(['message'=>'Ürün sepete eklendi.'],200);


        } catch (\Exception $e) {
            return response()->json([$e->getMessage()],500);
}
    }

    public function deleteCart(Request $request){
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'delete_all'=>'nullable|boolean'
            ]);
            
            $product = Product::findOrFail($request->product_id);

            $price = $product->is_discount_active ? $product->discount_price : $product->price;
            $maxStock = $product->is_discount_active ? $product->discount_stock : $product->stock;
            
            $user_id = Auth::user()->id;
            $cart = Cart::where('user_id',$user_id)->where('product_id',$product->id)->first();
            if (!$cart) {
                return response()->json(['message' => 'Ürün sepette bulunamadı!'], 404);
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
            if($cart->quantity> $maxStock){
                $cart->quantity=$maxStock;
                $cart->total = $cart->quantity * $price;
                $cart->save();
                return response()->json(['message'=>"Bu üründen en fazla {$maxStock} adet ekleyebilirsiniz."]);
            }
            $cart->total=$cart->quantity*$price;
            $cart->price=$price;
            $cart->save();
            
            return response()->json(['message'=>'Sepet güncellend,'],200);




        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }

    public function getUsersCart(){
        try {
            $user_id = Auth::user()->id;
            $carts = Cart::where('user_id',$user_id)->with('product')->get();
            if($carts->isEmpty()){
                return response()->json(['message'=>'Sepet boş.']);
            }

            $updatedCarts=$carts->map(function ($cart)
            {

                $product=$cart->product;
                if($product && $product->is_discount_active && $product->price !=$product->discount_price){
                    $cart->price = $product->discount_price;
                    $cart->total = $product->discount_price*$cart->quantity;
                }else{
                    $cart->price=$product->price;
                    $cart->total = $cart->quantity * $product->price;
                }
                return $cart;

            });


            return response()->json(['data'=>$updatedCarts],200);

        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }
}
