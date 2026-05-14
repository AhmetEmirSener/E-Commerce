<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            'advert_id'=>$this->advert_id,
            'rating'=>$this->rating,
            'comment'=>$this->comment ?? null,
            'status'=>$this->status,
            'comment_date' => $this->created_at->diffForHumans(),
            'comment_date_iso' => $this->created_at->toIso8601String(),
            'image' => $this->whenLoaded('advert', function () {
                return asset('storage/' . $this->advert->product->image);
            }),           
            'advert_slug' => $this->whenLoaded('advert', function () {
                return $this->advert->slug;
            }),
            'product_name' => $this->whenLoaded('advert', function () {
                return $this->advert->product->name;
            }),
            'user'=>  new UserResource($this->whenLoaded('user')),
        ];
    }
}
