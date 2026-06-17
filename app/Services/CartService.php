<?php

namespace App\Services;
use App\Models\Cart;
use App\Models\Advert;

use App\Models\CargoFee;
use Illuminate\Support\Facades\DB;
use Exception;

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

            $productPrice = $product->calculatedPrice();
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
        $quantitySum = $cart->sum('quantity');
        $noneSelected = $cart->where('is_selected',1)->isEmpty();
        if($cargoData && !$noneSelected && $cartTotal <$cargoData->free_shipping_threshold){
            $cargoFee = $cargoData->price;
        }

        if($noneSelected) $cargoFee=0;

        $subTotal = $cartTotal;
        $cartTotal = round($cartTotal+$cargoFee,2);

         return [
        'carts' => $cart,
        'summary'=>[
            'cartCount'=>$cartCount,
            'productCount' => $productCount,
            'quantitySum'=>$quantitySum,
            'subTotal'=>$subTotal,
            'cargoFee' => $cargoData->price ?? 0,
            'cartCargoFee'=>$cargoFee,
            'originalTotal' => $originalTotal,
            'discountTotal'=> $originalTotal >$subTotal ? $originalTotal - $subTotal : 0,
            'total' => $cartTotal,

        ],
        'priceChanged'=>$priceChanged ?? 0
  
        ];
        
        
    }   

    public function addOrUpdateCart(Advert $advert,int $quantity,int $user_id){

        $maxStock = $advert->product->stock;
     
        $quantityToAdd=$quantity??1;

        $productPrice = $advert->product->calculatedPrice();
        return DB::transaction(function () use ($advert,$productPrice, $maxStock,$quantityToAdd,$user_id){

            $cart = Cart::where('user_id',$user_id)->where('advert_id',$advert->id)
            ->lockForUpdate()->first();

            if($cart){

                if($maxStock <= 0){
                    $cart->delete();
                    return [
                        'status' => 'warning',
                        'message' => "Ürün stokta kalmadığı için sepetinizden kaldırıldı."
                    ];
                }

                if($cart->quantity == $maxStock){
                    throw new Exception("Alabileceğiniz en fazla ürün miktarı sepetinizde mevcut.", 400);
                }
                
                $cart->quantity+=$quantityToAdd;
                
                if($cart->quantity>$maxStock){
                    $cart->quantity=$maxStock;
                    $cart->total =$cart->quantity*$productPrice;
                    $cart->price =$productPrice;
                    $cart->save();
                    return [
                        'status' => 'warning',
                        'message' => "Bu üründen en fazla {$maxStock} adet ekleyebilirsiniz. Sepetiniz maksimum stoğa eşitlendi."
                    ];

                   // throw new Exception("Bu üründen en fazla {$maxStock} adet ekleyebilirsiniz.", 400);
                }
                $cart->total =$productPrice*$cart->quantity;
                $cart->price =$productPrice;
                $cart->save();

                return ['message' => 'Sepet güncellendi.'];

            }
            if($maxStock == 0){
                throw new Exception("Ürün stokları bitmiştir.", 400);
            }
            if($quantityToAdd>$maxStock){
                throw new Exception("Bu üründen en fazla {$maxStock} adet ekleyebilirsiniz.", 400);
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

            return ['message' => 'Ürün sepete eklendi.'];            
            
        });
    }

    public function deleteCart(int $user_id,Advert $advert, bool $delete_all=false)
    {


        return DB::transaction(function () use ($advert,$delete_all,$user_id){
            $cart = Cart::where('user_id',$user_id)->where('advert_id',$advert->id)
            ->lockForUpdate()->first();
            if(!$cart){
                throw new Exception("Ürün sepette bulunamadı.", 400);
                
            }
            if($delete_all){
                
                $cart->delete();
                return ['message' => 'Ürün sepetten kaldırıldı.'];            
            }
            

            $cart->quantity--;
            if($cart->quantity<=0){

                $cart->delete();
                return ['message' => 'Ürün sepetten kaldırıldı.'];            
            }

            $productPrice = $advert->product->calculatedPrice();
            $cart->syncPriceAndTotal($productPrice);

            $cart->save();

            return ['message' => 'Sepet güncellendi.'];            

        });

    }





    public function calculateTotalwCargoFee($total){
        $cargoData = CargoFee::where('is_active',1)->first();
        if($total< $cargoData->free_shipping_threshold){
            $total += $cargoData->price;
        }
        return $total;
    }
}
