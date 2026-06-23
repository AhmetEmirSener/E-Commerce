<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Advert;
use App\Models\CargoFee;
use App\Http\Requests\CartRequests\CartStoreRequest;
use App\Http\Requests\CartRequests\CartDeleteRequest;
use App\Http\Requests\CartRequests\CartUnselectRequest;

use App\Http\Resources\CartResource;

use App\Services\CartService;
use App\Services\AdvertService;

use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;



class CartController extends Controller
{
    protected CartService $cartService;
    protected AdvertService $advertService;


    public function __construct(CartService $cartService, AdvertService $advertService){
        $this->cartService=$cartService;
        $this->advertService=$advertService;

    }   


    public function storeCart(CartStoreRequest $request){
        try {
            $validated = $request->validated();

            $advert = $this->advertService->getForCartBySlug($validated['advert_slug'],['product','product.activeDiscount']);
            
            if(!$advert) return response()->json(['message'=>'Ürün eklenemedi'],404);
            if(!$advert->product) return response()->json(['message'=>'Ürün verisi eksik'],422);
            $user_id = $request->auth_user->id;
                
            $result = $this->cartService->addOrUpdateCart($advert,$validated['quantity'] ?? 1,$user_id);
          
            return response()->json($result, 200);
            

        } catch (\Exception $e) {
            $statusCode = $e->getCode() == 400 ? 400 : 500;
            return response()->json(['message' => $e->getMessage()], $statusCode);

        }
    }

    public function deleteCart(CartDeleteRequest $request){
        try {
            $validated = $request->validated();
            
            $advert = $this->advertService->getForCartBySlug($validated['advert_slug'],['product','product.activeDiscount']);
            
            if(!$advert) return response()->json(['message'=>'Ürün bulunamadı'],404);   
            if(!$advert->product) return response()->json(['message'=>'Ürün verisi eksik'],422);

            $user_id = $request->auth_user->id;

            $result = $this->cartService->deleteCart($user_id,$advert,$validated['delete_all']?? false);

            return response()->json($result,200);


        } catch (\Exception $e) {
            $statusCode = $e->getCode() == 400 ? 400 : 500;
            return response()->json(['message' => $e->getMessage()], $statusCode);
        }
    }

    public function changeSelected(CartUnselectRequest $request){
        try {
            $validated = $request->validated();
            $user_id = $request->auth_user->id;

            $advert = $this->advertService->getForCartBySlug($validated['advert_slug']);

            if(!$advert) return response()->json(['message'=>'Ürün bulunamadı'],404);
            
            $result = $this->cartService->changeSelected($user_id,$advert);

            return response()->json($result, 200);

        } catch (\Throwable $e) {
            $statusCode = $e->getCode() == 400 ? 400 : 500;
            return response()->json(['message' => $e->getMessage()], $statusCode);
        }
    }

    public function getCart(Request $request){
            
        $user_id = $request->auth_user->id;
        $cartData = $this->cartService->getUsersCarts($user_id);      

        return response()->json([
            'data'=>CartResource::collection($cartData['carts']),
            'summary'=>$cartData['summary'],
        ]);
      
    }

     public function checkoutCart(Request $request){
            
        $user_id = $request->auth_user->id;
        $cartData = $this->cartService->getUsersCarts($user_id,true);      

        return response()->json([
            'data'=>CartResource::collection($cartData['carts']),
            'summary'=>$cartData['summary'],
        ]);
      
    }


    public function cartCount(Request $request){
        $user = $request->get('auth_user');
        $cartCount = $this->cartService->getCartCount($user);
        return response()->json(['count'=>$cartCount]);
    
    }

}
