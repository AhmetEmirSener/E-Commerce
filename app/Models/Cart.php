<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Cart extends Model
{
    protected $guarded = [];

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function syncPriceAndTotal(float $currentPrice){
        $this->price = $currentPrice;

        $this->total = $currentPrice * $this->quantity;
    }
}
