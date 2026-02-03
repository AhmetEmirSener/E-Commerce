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
            'advert_id'=>$this->advert_id,
            'rating'=>$this->rating,
            'comment'=>$this->comment ?? null,
            'status'=>$this->status,
            'comment_date' => $this->created_at->diffForHumans(),
            'product_image'=>$this->product->image ?? null,
            'user'=> new UserResource($this->user)
        ];
    }
}
