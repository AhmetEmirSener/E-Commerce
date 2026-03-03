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
        $product = $this->product;
        $advert = $product?->advert;
        $discount = $product?->activeDiscount;
        return[
            'id'=>$advert->id,
            'category_id'=>$advert->category_id,
            'title'=>$advert->title,
            'slug'=>$advert->slug,
            'avg_rating'=>$advert->avg_rating,
            'total_comments'=>$advert->total_comments,

            'image'=>$product->image ? asset('storage/'.$product->image):null,
            'original_price'=>$product->price,
            'discount_price' => $this->discount_price, 

        ];
    }
}

