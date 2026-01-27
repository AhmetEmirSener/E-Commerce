<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class miniProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            'id'=>$this->id,
            'price'=>$this->price,
            'discount_price'=>$this->discount_price,
            'is_discount_active'=>$this->is_discount_active,
            'image'=>$this->image ? asset('storage/'.$this->image):null
        ];
    }
}
