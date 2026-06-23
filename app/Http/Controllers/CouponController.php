<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Cart;
use App\Models\User;
use App\Services\CartService;
use App\Services\CouponService;

use App\Http\Requests\Coupon\CouponRequest;
use App\Http\Resources\CartResource;
use App\Http\Resources\Coupon\CouponResource;

use Illuminate\Http\Request;

class CouponController extends Controller
{

    protected CartService $cartService;
    protected CouponService $couponService;

    public function __construct(CartService $cartService, CouponService $couponService){
        $this->cartService = $cartService;
        $this->couponService = $couponService;

    }

    public function activeCoupon(CouponRequest $request){
        $validated = $request->validated();

        $user = $request->get('auth_user');

        try {
            $coupon = $this->couponService->validateCoupon($validated['coupon_code'], $user->id);

            $carts = Cart::where('user_id', $user->id)
                ->where('is_selected', 1)
                ->lockForUpdate()
                ->with('product.advert', 'product.activeDiscount')
                ->get();

            $cartData = $this->cartService->updatedCart($carts, $coupon);
            
            return response()->json([
                'carts'   => CartResource::collection($cartData['carts']),
                'summary' => $cartData['summary'],
                'coupon'  => new CouponResource($coupon)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400); 
        }

    }
    
  



}
