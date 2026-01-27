<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\miniAdvertResource;

class popularAdvertsByCate extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       return [
            'popular_adverts'=> miniAdvertResource::collection($this->whenLoaded('popularAdverts')),
       ];
    }
}
