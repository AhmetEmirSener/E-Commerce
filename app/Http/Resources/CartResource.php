<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\miniAdvertResource;


class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {   
        $product = $this->product;
        $discount = $product->activeDiscount;
        $advert = $this->product->advert;
        $discountPrice = $product?->activeDiscount?->discount_price ?? null;
        $discountType = $product?->activeDiscount?->discount_type ?? null;
        $discountValue = $product?->activeDiscount?->discount_value ?? null;

        return [
            'price'=>$this->price,
            'quantity'=>$this->quantity,
            'total'=>$this->total,
            'beforeDiscountTotal'=>$discount ? $product->price * $this->quantity : 0,
            'is_selected'=>$this->is_selected,
            'title'=>$advert->title,
            'slug'=>$advert->slug,
            'avg_rating'=>$advert->avg_rating,
            'total_comments'=>$advert->total_comments,

            'image'=>$product->image ? asset('storage/'.$product->image):null,

            'original_price'=>$product?->price,
            'discount_price'=> $discountPrice,
            'discount_type'=>$discountType,
            'discount_value'=>$discountValue,

            
        ];
    }
}
