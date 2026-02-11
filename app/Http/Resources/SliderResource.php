<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SliderResource extends JsonResource
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
             'type'=>$this->type, 
             'title'=>$this->title,
             'sort'=>$this->sort,
             'ref_type' => $this->items->first()?->ref_type,
                'items' => $this->items->map(function($item) {
                return match ($item->ref_type) {
                    'product', 'advert' => new miniAdvertResource($item->advert),
                    'category' => new CategoryResource($item->category),
                    'campaign'=> new CampaignResource($item->campaign),
                    default => null
                };

                }),
            ];
    }
}
