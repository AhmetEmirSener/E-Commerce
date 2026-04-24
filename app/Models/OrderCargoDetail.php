<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCargoDetail extends Model
{
    protected $guarded = [];
   

    public function order(){
        return $this->belongsTo(Order::class);
    }

    public function cargoItems(){
        return $this->hasMany(CargoItem::class);
    }
}
