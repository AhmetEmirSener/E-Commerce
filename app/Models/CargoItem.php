<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargoItem extends Model
{
    protected $guarded = [];

    
    public function orderItem(){
        return $this->belongsTo(OrderItem::class);
    }

    public function OrderCargoDetail(){
        return $this->belongsTo(OrderCargoDetail::class);
    }
}
