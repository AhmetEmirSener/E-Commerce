<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\ProductImageResource;

class ProductResource extends JsonResource
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
            'name'=>$this->name,
            'category_id'=>$this->category_id,
            'brand_id'=>$this->brand_id ?? null,
            'price'=>$this->price,
            'discount_price'=>$this->discount_price,
            'discount_stock'=>$this->discount_stock,
            'is_discount_active'=>$this->is_discount_active,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'weight'=>$this->weight??null,
            'stock'=>$this->stock,
            'slug'=>$this->slug,
            'status'=>$this->status,
            'features'=>$this->features,
            'images'=> (ProductImageResource::collection($this->images ?? collect())),

        ];
    }
}
