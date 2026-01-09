<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProductResource;

class AdvertResource extends JsonResource
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
            'product_id'=>$this->product_id,
            'title'=>$this->title,
            'description'=>$this->description,
            'avg_rating'=>$this->avg_rating,
            'total_comments'=>$this->total_comments,
            'images'=>$this->images,
            'views'=>$this->views,
            'price'=>$this->price,
            'status'=>$this->status,
            'is_featured'=>$this->is_featured,
            'item_ref'=> new ProductResource($this->product)
        ];
    }
}
