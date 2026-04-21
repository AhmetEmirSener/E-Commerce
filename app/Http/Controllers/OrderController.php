<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Resources\OrderResource;
use App\Http\Resources\AddressResource;

use App\Http\Resources\OrderWithPaymentDetails;

class OrderController extends Controller
{
    public function orders(Request $request){
        try {
            $user = $request->get('auth_user');    
            $allowedSorts = ['pending','completed','shipped','cancelled'];

            $orders = Order::query()
            ->where('user_id',$user->id)->with('payment','orderItems.product')->withCount('orderItems')
            ->when(in_array($request->status,$allowedSorts), function ($query) use ($request){
                $query->where('status',$request->status);
            })->latest()->paginate(7);
           
            return response()->json([
                'data'=>OrderResource::collection($orders),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'total' => $orders->total(),
                    ]
            ]);

        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()],500);
        }
    }

    public function order(Request $request,$id){
        try {
            $user = $request->get('auth_user');    
            $order = Order::where('id',$id)->with('payment','orderItems.product')->withCount('orderItems')->first();

            if(!$order){
                return response()->json(['message'=>'Sipariş bulunamadı'],404);
            }
            if($order->user_id !== $user->id){
                return response()->json(['message','Sipariş bulunamadı'],404);
            }
           
            return response()->json([
                'data'=>new OrderWithPaymentDetails($order),
            ]);

        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()],500);
        }
    }
}
