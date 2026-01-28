<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryChildResource extends JsonResource
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
            'name'=>$this->name,
            'parent_id'=>$this->parent_id,
            'slug'=>$this->slug,
            'image'=>$this->image ? asset('storage/'. $this->image): null,
            'children'=>CategoryChildResource::collection($this->getChild ?? collect()) 
        ];
    }
}
