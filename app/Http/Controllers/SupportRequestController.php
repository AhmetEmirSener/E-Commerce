<?php

namespace App\Http\Controllers;

use App\Models\SupportRequest;
use App\Models\Order;

use Illuminate\Http\Request;

use App\Http\Requests\SupportRequestCreate;

class SupportRequestController extends Controller
{
    public function createSupport(SupportRequestCreate $request){
        $data = $request->validated();

        try {
            
            if(!empty($data['order_id']) && !empty($data['user_id'])){
                $order= Order::where('user_id',$data['user_id'])->where('id',$data['order_id'])->first();
                if(!$order){
                    return response()->json(['message'=>'Girilien sipariş numarası geçersiz.'],404);
                }
            }


            if (!empty($data['order_id']) && empty($data['user_id'])) {
                $data['order_id'] = null;
            }
            SupportRequest::create($data);
            
            return response()->json(['message'=>'Destek kaydı oluşturuldu. Ortalama dönüş süresi 1 iş günüdür.'],200);
        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()],500);
        }

    }
}
