<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderWithPaymentDetails extends OrderResource 
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'last_four' => $this->payment->last_four,
            'card_bank' => $this->payment->card_bank,
            
        ]);
    }
}
