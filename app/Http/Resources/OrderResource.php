<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\OrderItemResource;
use Carbon\Carbon;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {

    $payment =$this->payment;

    return [
        'ordered_at' => Carbon::parse($this->ordered_at,)
        ->timezone('Europe/Istanbul')
        ->locale('tr')
        ->translatedFormat('d F Y H:i'),

        'users_address_id'=>$this->user_address_id,
        'total'=>$this->total,
        'subTotal'=>$this->subTotal,
        'installment_fee'=>$payment->installment_fee,
        'installment'=>$payment->installment_count,
        'cargo_fee'=>$this->cargo_fee,
        'payment_status'=>$payment->status,
        'status'=>$this->status,
        'order_items'=>$this->orderItems ? OrderItemResource::collection($this->whenLoaded('orderItems')) : null,

    ];
    

    }

        
    
}
