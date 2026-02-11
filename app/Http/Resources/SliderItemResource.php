<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\AdvertResource;
use App\Http\Resources\CampaignResource;

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
            'ref' => match ($this->ref_type) {
                'product', 'advert' => new miniAdvertResource($this->advert),
                'category' => new CategoryResource($this->category),
                'campaign'=> new CampaignResource($this->campaign),
                default => null
            }
        ];
    }
}
