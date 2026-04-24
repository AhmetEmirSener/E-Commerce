<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundRequestItemsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {   
        $orderItem = $this->orderItem ?? null;
        $product = $orderItem->product ?? null;
        return[
            'id'=>$this->id,
            'refund_request_id'=>$this->refund_request_id,
            'order_item_id'=>$this->order_item_id,
            'quantity'=>$this->quantity,
            'amount'=>$this->amount,
            'image' => $product->image ? asset('storage/' . $product->image) : null,
            'approved_quantity'=>$this->approved_quantity,
            'rejected_quantity'=>$this->quantity - $this->approved_quantity
        ];
    }
}
