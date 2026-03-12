<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductImageResource;

class AdvertResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product= $this->product;
        $advert=$this->resource;
        $discountPrice=$product?->activeDiscount->discount_price ?? null;
        $discountType=$product?->activeDiscount->discount_type ?? null;
        $discountValue=$product?->activeDiscount->discount_value ?? null;


        return[
            'id'=>$this->id,
            'category_id'=>$this->category_id,
            'title'=>$this->title,
            'slug'=>$this->slug,
            'description'=>$this->description,
            'avg_rating'=>$this->avg_rating,
            'total_comments'=>$this->total_comments,

            'views'=>$this->views,
            'is_featured'=>$this->is_featured,
            'original_price'=> $product?->price,
            'discount_price'=> $discountPrice,
            'discount_type'=>$discountType,
            'discount_value'=>$discountValue,
            'features'=>$product->features,
            'images'=> ProductImageResource::collection($product->images) 
            //'item_ref'=> new ProductResource($this->product)
        ];
    }
}
