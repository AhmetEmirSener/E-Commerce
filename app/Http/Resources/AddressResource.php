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
            'id'=>$this->id,
            'full_name'=>$this->full_name,
            'phone_number'=>$this->phone_number,
            'address_type'=>$this->address_type,
            'address_line'=>$this->address_line,
            'city_id'=>$this->city_id,
            'city'=>$this->city,
            'state_id'=>$this->state_id,
            'state'=>$this->state,
            'neighbourhood'=>$this->neighbourhood,
            'postal_code'=>$this->postal_code,
            'is_default'=>$this->is_default


        ];
    }
}
