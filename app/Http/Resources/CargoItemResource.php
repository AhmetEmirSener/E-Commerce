<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CargoItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {  $orderItem = $this->orderItem ?? null;
        $product = $orderItem->product ?? null;
        return [
            'order_cargo_detail_id'=>$this->order_cargo_detail_id,
            'order_item_id'=>$this->order_item_id,
            'quantity'=>$this->quantity,
            'image' => $product->image ? asset('storage/' . $product->image) : null,


        ];
    }
}
