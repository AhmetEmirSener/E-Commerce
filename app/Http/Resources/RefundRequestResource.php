<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\RefundRequestItemsResource;

class RefundRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            'order_id'=>$this->id,
            'status'=>$this->status,
            'reason'=>$this->reason,
            'cargo_tracking_code'=>$this->cargo_tracking_code,
            'cargo_company'=>$this->cargo_company,
            'shipped_at'=>$this->shipped_at,
            'received_at'=>$this->received_at,
            'admin_note'=>$this->admin_note,
            'items'=>$this->refundRequestItem ? RefundRequestItemsResource::collection($this->whenLoaded('refundRequestItem')) : null,
        ];
    }
}
