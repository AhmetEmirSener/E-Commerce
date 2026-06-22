<?php

namespace App\Services;
use App\Models\Coupon;
use App\Models\Cart;

class CouponService
{
    /**
     * Create a new class instance.
     */
      public function checkCoupon(Coupon $coupon, $cartItems){

        //$coupon = Coupon::where('code',$coupon_code)->with('rules')->first();
      
        $rules = $coupon->rules;
        

        //$cartItems = Cart::where('user_id',$user->id)->with('product','product.category')->where('is_selected',1)->get();

        $cartTotal = $cartItems->sum('total');
        $cartRule = $rules->where('field','cart')->first();

        if($cartRule){
            $isValid = $this->isValid($cartRule,$cartTotal);
            
            if(!$isValid){
                return response()->json('Kupon sepetinize uygulanamaz.',400);
            }
        }

        $validItems = $cartItems;
        $itemRules = $rules->where('field', '!=', 'cart');

        if($itemRules->isNotEmpty()){
            
            foreach($itemRules as $rule){
                $validItems = $validItems->filter(function ($item) use ($rule){
                    $value = data_get($item,$rule->field);
                    return $this->isValid($rule,$value);
                });
            }
        }

        $validIds = $validItems->pluck('id');
        $total = $validItems->sum('total');

        if($total <= 0 || $cartItems->isEmpty()){ // or !$cartItems->isEmpty()
            return response()->json('Kupon sepetinize uygulanamaz'); 
        }
        if($cartRule){

            $cartRuleStatus = $this->isValid($cartRule,$total);
            
            if(!$cartRuleStatus){
                return response()->json('Kupon sepetinize uygulanamaz.',400);
            }

        }
        return $this->calculateCouponDiscount($coupon,$cartItems,$validIds,$total);
          

    }


       public function calculateCouponDiscount(Coupon $coupon, $cartItems,$validIds,float $total){
        $couponDiscountTotal = 0;
        
        foreach($cartItems->whereIn('id',$validIds) as $cartItem){
            if($coupon->discount_type == 'percent'){

                $discount = round($cartItem->total * $coupon->discount_value / 100,2);
                $cartItem->coupon_discount_total = $discount;
                $cartItem->total = round($cartItem->total - $cartItem->coupon_discount_total,2);
                $couponDiscountTotal+=$cartItem->coupon_discount_total;
              

            }else{
                $cartPercent = $cartItem->total / $total;
                $cartItem->coupon_discount_total = round($cartPercent * ($coupon->discount_value),2);

                $cartItem->total = round($cartItem->total - $cartItem->coupon_discount_total,2);
                $couponDiscountTotal+=$cartItem->coupon_discount_total;

            }
            
        }
        return ['cart'=>$cartItems, 'couponDiscountTotal'=>$couponDiscountTotal];

    }



 

    private function isValid($rule, $cartTotal){
       return match ($rule->operator) {
            '>'  => $cartTotal > $rule->value,
            '>=' => $cartTotal >= $rule->value,
            '<'  => $cartTotal < $rule->value,
            '<=' => $cartTotal <= $rule->value,
            '==' => $cartTotal == $rule->value,
            '!=' => $cartTotal != $rule->value,
            default => false,
        };
    }
}
