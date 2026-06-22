<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Cart;
use App\Models\User;
use App\Services\CartService;

use Illuminate\Http\Request;

class CouponController extends Controller
{

    protected CartService $cartService;

    public function __construct(CartService $cartService){
        $this->cartService = $cartService;
    }

    public function activeCoupon(Request $request){
        $user = $request->get('auth_user');
        $coupon =  Coupon::where('code',$request->coupon_code)->with('rules')->first();
        
        if(!$coupon){
            return response()->json(['message'=>'Kupon kodu geçersiz.'],400);
        }

        $carts = Cart::where('user_id',$user->id)->where('is_selected',1)->lockForUpdate()->with('product.advert','product.activeDiscount')->get();

        return $this->cartService->updatedCart($carts,$coupon);

    }
    
  



}
