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

    public function updatedCart($cart){
        $originalTotal = 0;
        $discountTotal=0;
        $productCount=0;
        $cartTotal=0;
        $cartCount=0;
        $cargoData = CargoFee::where('is_active',1)->first();
        $cargoFee=0;

        $priceChanged = 0;
        $cart->map(function ($cart) use(&$productCount,&$originalTotal,&$discountTotal,&$cartTotal,&$cargoData,&$priceChanged){
            $product = $cart->product;
           // $discount = $product->activeDiscount;

            $productPrice = $product->activeDiscount ? $product->activeDiscount->discount_price : $product->price;
            if($cart->price !== $productPrice){
                $priceChanged++;
            }
            $cart->price = $productPrice;
            $cart->total = $productPrice * $cart->quantity;
            
            /*
            if($discount){
                $cart->beforeDiscountTotal = $product->price * $cart->quantity;
            }
            */
            if($cart->is_selected){
                $productCount +=$cart->quantity;
                
                $cartTotal+= $cart->total;
                $originalTotal += $cart->quantity * $product->price;

            }

        });
        $cartCount = $cart->count();
        $noneSelected = $cart->where('is_selected',1)->isEmpty();
        if($cargoData && !$noneSelected && $cartTotal <$cargoData->free_shipping_threshold){
            $cargoFee = $cargoData->price;
        }

        if($noneSelected) $cargoFee=0;

        $subTotal = $cartTotal;
        $cartTotal+=$cargoFee;
         return [
        'carts' => $cart,
        'summary'=>[
            'cartCount'=>$cartCount,
            'subTotal'=>$subTotal,
            'productCount' => $productCount,
            'cargoFee' => $cargoData->price ?? 0,
            'cartCargoFee'=>$cargoFee,
            'originalTotal' => $originalTotal,
            'discountTotal'=> $originalTotal >$subTotal ? $originalTotal - $subTotal : 0,
            'total' => $cartTotal,

        ],
        'priceChanged'=>$priceChanged ?? 0
  
        ];
        
        
    }   
}
