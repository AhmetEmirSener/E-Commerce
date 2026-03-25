<?php

namespace App\Services;
use App\Models\Cart;
use App\Models\CargoFee;

class CartService
{
    /**
     * Create a new class instance.
     */
    
    public function __construct()
    {   
       
    }

    public function updatedCart($cart,$update){
        $originalTotal = 0;
        $discountTotal=0;
        $productCount=0;
        $cartTotal=0;
        $cartCount=0;
        $cargoData = CargoFee::where('is_active',1)->first();
        $cargoFee=0;


        $cart->map(function ($cart) use(&$productCount,&$originalTotal,&$discountTotal,&$cartTotal,&$cargoData){
            $product = $cart->product;
            $discount = $product->activeDiscount;

            $productPrice = $product->activeDiscount ? $product->activeDiscount->discount_price : $product->price;

            $cart->price = $productPrice;
            $cart->total = $productPrice * $cart->quantity;

            if($discount){
                $cart->beforeDiscountTotal = $product->price * $cart->quantity;
            }
            if($cart->is_selected){
                $productCount +=$cart->quantity;
                
                $cartTotal+= $cart->total;
                $originalTotal += $cart->quantity * $product->price;

            }

        });
        $cartCount = $cart->where('is_selected',1)->count();
        
        if($cargoData && $cartTotal <$cargoData->free_shipping_threshold){
            $cargoFee = $cargoData->price;
        }
        $cartTotal+=$cargoFee;
         return [
        'carts' => $cart,
        'summary'=>[
            'cartCount'=>$cartCount,
            'productCount' => $productCount,
            'cargoFee' => $cargoData->price,
            'cartCargoFee'=>$cargoFee,
            'originalTotal' => $originalTotal,
            'discountTotal'=> $originalTotal >$cartTotal ? $originalTotal - $cartTotal : 0,
            'total' => $cartTotal,

        ]
  
        ];
        
        
    }   
}
