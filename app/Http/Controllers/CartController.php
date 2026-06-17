<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Advert;
use App\Models\CargoFee;
use App\Http\Requests\CartRequests\CartStoreRequest;
use App\Http\Requests\CartRequests\CartDeleteRequest;

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

    public function changeSelected(Request $request){
        try {
            $request->validate([
                'advert_slug' => 'required',
            ]);
            $user_id = $request->auth_user->id;
            $advert = Advert::where('slug',$request->advert_slug)->first();

            if(!$advert) return response()->json(['message'=>'Ürün bulunamadı']);
            $cart = Cart::where('user_id',$user_id)->where('advert_id',$advert->id)->first();    

            if(!$cart) return response()->json(['message'=>'Sepetteki ürün bulunamadı']);
            $cart->is_selected =!$cart->is_selected;
            $cart->save();

            return response()->json(['message'=>'Sepet güncellendi']);
        } catch (\Throwable $th) {
            return response()->json([$th->getMessage()],500);
        }
    }

      public function getUsersCart(Request $request){
        try {
            $user_id = $request->auth_user->id;
            $carts = Cart::where('user_id',$user_id)->with('product.advert','product.activeDiscount')->get();
          
            if($carts->isEmpty()){
                return response()->json([
                    'data' => [],
                    'summary' =>$this->emptySummary()
                ], 200);
            }
          
            $cartService = $this->cartService->updatedCart($carts);

            return response()->json([
                'data'=>CartResource::collection($cartService['carts']),
                'summary'=>$cartService['summary'],
              
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }


    private function emptySummary(){
        return [
            'count'=> 0,
            'cartCount'=>0,
            'subTotal'=> 0,
            'cargoFee'=> 0,
            'cargoCartFee'=> 0,
            'total'=> 0
        ];
    }
}
