<?php

namespace App\Services;
use App\Models\Coupon;
use App\Models\CouponUsage;

use App\Models\Cart;
use Illuminate\Support\Facades\Cache;

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
                throw new \Exception('Kupon sepetinize uygulanamaz.');

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
            
            throw new \Exception('Kupon sepetinize uygulanamaz.');

        }
        if($cartRule){

            $cartRuleStatus = $this->isValid($cartRule,$total);
            
            if(!$cartRuleStatus){
                throw new \Exception('Kupon sepetinize uygulanamaz.');
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
        return ['cart'=>$cartItems, 'couponDiscountTotal'=>$couponDiscountTotal, 'coupon'=>$coupon];

    }

    public function validateCoupon(string $coupon_code,int $user_id){
        $coupon = Coupon::where('code',$coupon_code)->where('is_active',1)->with('rules')->first();
        if(!$coupon){
            throw new \Exception('Kupon geçersiz.');
        }
        if($coupon->end_date < now()){
           throw new \Exception('Kupon süresi geçmiş.');
        }
        if($coupon->usage_limit <= 0){
           throw new \Exception('Kupon tükendi.');
        }
       
        $completedUsagesCount = CouponUsage::where('user_id', $user_id)->where('coupon_id', $coupon->id)
        ->where('status', 'completed')
        ->count();

        if($completedUsagesCount >= $coupon->user_usage_limit){
            throw new \Exception('Bu kuponun kullanım sınırına ulaştınız.');
        }

        return $coupon;

    }


    public function createCouponUsage(Coupon $coupon,int $order_id, int $user_id){
        $reserveMinutes = $coupon->reserve_minutes ?? 15;
  

        $usedCoupon = CouponUsage::where('user_id',$user_id)->where('coupon_id',$coupon->id)->first();

        if($usedCoupon && in_array($usedCoupon->status, ['pending', 'cancelled'])) {
   
            if ($usedCoupon->status === 'cancelled') {

                $this->decreaseCouponLimit($coupon); 
            }

            $currentExpiry = \Carbon\Carbon::parse($usedCoupon->expires_at);

            $threshold = max(2, $reserveMinutes * 0.2);
            $needsExtension = false;
            if($currentExpiry->isPast()){
                $baseTime = now()->addMinutes(max(10, $reserveMinutes * 0.5));
                $needsExtension = true;
            }else{
                $remainingMinutes = now()->diffInMinutes($currentExpiry, false);
                if ($remainingMinutes <= 5) {
                    
                    $baseTime = $currentExpiry->addMinutes($threshold);    
                    $needsExtension = true;            
                } else {
                    $baseTime = $currentExpiry;
            }
            }
            
            if($needsExtension){
                $rateKey = 'coupon_rate_limit_user:' . $user_id . '_coupon:' . $coupon->id;
        
        
                $cache = Cache::get($rateKey);

                if (!$cache) {
                    Cache::put($rateKey, ['attempts' => 1], now()->addMinutes($reserveMinutes));
                } else {
                    if ($cache['attempts'] >= 3) {
                        // return $usedCoupon;  kupon rezerve 5dk'den az kaldı izin ver ? verme ? kararlaştır sonra.
                         
                        throw new \Exception('Bu kuponun işlem sınırına ulaştınız, lütfen ' . $reserveMinutes . ' dakika sonra tekrar deneyiniz.');
                    }
                    
                    $cache['attempts']++;

                    Cache::put($rateKey, $cache, now()->addMinutes($reserveMinutes));
                }
            }

            $usedCoupon->expires_at = $baseTime;
            $usedCoupon->status = 'pending';
            $usedCoupon->order_id = $order_id;
            $usedCoupon->save();
            return $usedCoupon;
        }
        
        $couponUsage = CouponUsage::create([
            'user_id'=>$user_id,
            'order_id'=>$order_id,
            'coupon_id'=>$coupon->id,
            'status'=>'pending',
            'expires_at' => now()->addMinutes($coupon->reserve_minutes ?? 15)            
        ]);
        $this->decreaseCouponLimit($coupon);
        return $couponUsage;
    }

    public function decreaseCouponLimit(Coupon $coupon){
        if($coupon->usage_limit <= 0){
            throw new \Exception('Kupon tükendi.');
            
        }

        $coupon->usage_limit--;
        $coupon->save();
    }

    public function increaseCouponLimit(Coupon $coupon){
        $coupon->usage_limit++;
        $coupon->save();
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
