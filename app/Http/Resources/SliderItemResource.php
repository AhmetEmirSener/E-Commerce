<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\AdvertResource;

class SliderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->ref_type,
            'image' => $this->image,
            'mobile_image' => $this->mobile_image,
            'sort' => $this->sort,
            'ref' => match ($this->ref_type) {
                'product', 'advert' => new AdvertResource($this->advert),
                'category' => new CategoryResource($this->category),
                default => null
            }
        ];
    }
}
