<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CargoItemResource;
use Carbon\Carbon;

class OrderCargoDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            
            'order_id'=>$this->order_id,
            'cargo_company'=>$this->cargo_company,
            'tracking_code'=>$this->tracking_code,
            'tracking_url'=>$this->tracking_url,
            'status'=>$this->status,
            'shipped_at'=>Carbon::parse($this->shipped_at,)
            ->timezone('Europe/Istanbul')
            ->locale('tr')
            ->translatedFormat('d F Y '),
            'delivered_at'=>$this->delivered_at,
            'notes'=>$this->notes,
            'items'=>$this->cargoItems ? CargoItemResource::collection($this->whenLoaded('cargoItems')) : null
        ];
    }
}
