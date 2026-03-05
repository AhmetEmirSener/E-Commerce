<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\miniProductResource;

class miniAdvertResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof \App\Models\ProductDiscount) {
            $product = $this->product;
            $advert  = $product?->advert;
            $discountPrice = $this->discount_price;
            $discountType = $this->discount_type;
            $discountValue = $this->discount_value;

            
        } else {
            // Advert geliyor
            $product = $this->product;
            $advert  = $this->resource;
            $discountPrice = $product?->activeDiscount?->discount_price ?? null;
            $discountType = $product?->activeDiscount?->discount_type ?? null;
            $discountValue = $product?->activeDiscount?->discount_value ?? null;

        }
        return[
            'id'=>$advert->id,
            'category_id'=>$advert->category_id,
            'title'=>$advert->title,
            'slug'=>$advert->slug,
            'avg_rating'=>$advert->avg_rating,
            'total_comments'=>$advert->total_comments,

            'image'=>$product->image ? asset('storage/'.$product->image):null,
            'original_price'=> $product?->price,
            'discount_price'=> $discountPrice,
            'discount_type'=>$discountType,
            'discount_value'=>$discountValue

        ];
    }
}

