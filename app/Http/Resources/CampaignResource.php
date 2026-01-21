<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
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
            'title'=>$this->title,
            'slug'=>$this->slug,
            'description'=>$this->description,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'mobile_image'=>$this->mobile_image ? asset('storage/'. $this->mobile_image): null,
            'link'=>$this->link,
            'start_date'=>$this->start_date,
            'end_date'=>$this->end_date
        ];
    }
}
