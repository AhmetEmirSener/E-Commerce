<?php

namespace App\Http\Resources\Coupon;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $discount_type = $this->discount_type;
        $discont_value = $this->discount_value;
        $discount = $discount_type== 'percent' ? $discont_value.'%' : $discont_value.' TL indirim.';
        return[
            'code'=>$this->code,
            'discount'=>$discount
          

        ];
    }
}
