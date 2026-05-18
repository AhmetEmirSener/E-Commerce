<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

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
