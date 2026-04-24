<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
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
            'order_id'=>$this->order_id,
            'amount'=>$this->amount,
            'status'=>$this->status,
            'id'=>$this->id,
            'id'=>$this->id,
        ];
    }
}
