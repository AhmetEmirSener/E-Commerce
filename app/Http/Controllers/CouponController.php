<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Cart;

use Illuminate\Http\Request;

class CouponController extends Controller
{
    
    public function checkCoupon(Request $request){
        $user = $request->get('auth_user');
        $coupon = Coupon::where('code',$request->code)->with('rules')->first();
        $rules = $coupon->rules;
        $cartItems = Cart::where('user_id',$user->id)->with('product','product.category')->where('is_selected',1)->get();

        $cartTotal = $cartItems->sum('total');
        $cartRule = $rules->where('field','cart')->first();

        $unValidTotal =0 ;

        if($cartRule){
            $isValid = $this->isValid($cartRule,$cartTotal);
            
            if(!$isValid){
                return response()->json('Kupon sepetinize uygulanamaz.',400);
            }
        }

        foreach($rules->where('field','!=','cart') as $rule){
            foreach($cartItems as $item){
            
            $value = data_get($item,$rule->field);
            

            $isValid = $this->isValid($rule,$value);

            if(!$isValid){
                $unValidTotal += $item->total;
                $cartItems= $cartItems->except($item->id);
            }

            }

            
        }

        $total = round($cartTotal - $unValidTotal,2);

        if($total <= 0 || $cartItems->isEmpty()){ // or !$cartItems->isEmpty()
            return response()->json('Kupon sepetinize uygulanamaz');
        }
        if($cartRule){

            $cartRuleStatus = $this->isValid($cartRule,$total);
            
            if(!$cartRuleStatus){
                return response()->json('Kupon sepetinize uygulanamaz.',400);
            }

        }
        dd($total);
           // Sepet summary çağırılacak

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
