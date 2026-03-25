<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'full_name'=>$this->full_name,
            'phone_number'=>$this->phone_number,
            'address_line'=>$this->address_line,
            'city'=>$this->city,
            'state'=>$this->state,
            'neighbourhood'=>$this->neighbourhood,
            'postal_code'=>$this->postal_code,
            'is_default'=>$this->is_default


        ];
    }
}
