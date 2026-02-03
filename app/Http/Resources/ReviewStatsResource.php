<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */



     private function percentage($count,$total){
        return $total ? round(($count/$total)*100,1) : 0;
     }



     public function toArray(Request $request): array
     {
         return [
             'avg' => round($this->avg, 1),
             'total' => $this->total,
 
             'five' => $this->five,
             'five_avg' => $this->percentage($this->five, $this->total),
 
             'four' => $this->four,
             'four_avg' => $this->percentage($this->four, $this->total),
 
             'three' => $this->three,
             'three_avg' => $this->percentage($this->three, $this->total),
 
             'two' => $this->two,
             'two_avg' => $this->percentage($this->two, $this->total),
 
             'one' => $this->one,
             'one_avg' => $this->percentage($this->one, $this->total),
         ];
     }
}
