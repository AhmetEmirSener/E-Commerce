<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\RefundRequestItemsResource;
use Carbon\Carbon;

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

            'shipped_at' => $this->shipped_at
                ? Carbon::parse($this->shipped_at)
                    ->timezone('Europe/Istanbul')
                    ->locale('tr')
                    ->translatedFormat('d F Y')
                : null,

            'received_at' => $this->received_at
                ? Carbon::parse($this->received_at)
                    ->timezone('Europe/Istanbul')
                    ->locale('tr')
                    ->translatedFormat('d F Y')
                : null,

            'created_at' => $this->created_at
                ? Carbon::parse($this->created_at)
                    ->timezone('Europe/Istanbul')
                    ->locale('tr')
                    ->translatedFormat('d F Y')
                : null,
    
            'admin_note'=>$this->admin_note,
            'items'=>$this->refundRequestItem ? RefundRequestItemsResource::collection($this->whenLoaded('refundRequestItem')) : null,
        ];
    }
}
