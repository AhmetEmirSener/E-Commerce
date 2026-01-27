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
        return[
            'id'=>$this->id,
            'category_id'=>$this->category_id,
            'title'=>$this->title,
            'slug'=>$this->slug,
            'avg_rating'=>$this->avg_rating,
            'total_comments'=>$this->total_comments,
            'item_ref'=>  new miniProductResource($this->product),
        ];
    }
}
