<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            'name'=>Str::mask(Str::limit($this->name,4,''), '*', 1),
            'surname'=>Str::mask(Str::limit($this->surname,4,''), '*', 1)
        ];
    }
}
