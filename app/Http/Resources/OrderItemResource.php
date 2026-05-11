<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product= $this->product;
        return [
            'id'=>$this->id,
            'quantity'=>$this->quantity,
            'price'=>$this->price,
            'original_total'=>$this->original_price * $this->quantity,

            'total'=>$this->total,
            'quantity'=>$this->quantity,
            'name'=>$product->name,
            'image' => $product->image ? asset('storage/' . $product->image) : null,
            'review' => $this->whenLoaded('review', function () {
                return [
                    'id' => $this->review->id,
                    'rating'=>$this->review->rating
                ];
            }),
        ];
    }
}
