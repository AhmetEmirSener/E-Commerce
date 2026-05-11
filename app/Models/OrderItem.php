<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $guarded = [];

    public function product(){
        return $this->belongsTo(product::class);
    }

    public function refundRequestItem(){
        return $this->hasMany(RefundRequestItem::class);
    }

    public function cargoItem(){
        return $this->hasOne(CargoItem::class);
    }

    public function order(){
        return $this->belongsTo(Order::class);
    }

    public function review(){
        return $this->hasOne(Review::class);
    }
}
