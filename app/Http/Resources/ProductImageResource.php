<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            'path'=>$this->path ? asset('storage/' . $this->path) : null,
            'title'=>$this->title,
            'sort'=>$this->sort,
            'is_main'=>$this->is_main,
        ];
    }
}
