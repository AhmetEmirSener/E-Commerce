<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'shipping_address' => 'array',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function orderItems(){
        return $this->hasMany(OrderItem::class);
    }

    public function payment(){
        return $this->hasOne(Payment::class);
    }


    public function refundRequest(){
        return $this->hasMany(RefundRequest::class);
    }

    

    public function refund(){
        return $this->hasMany(Refund::class);
    }


    public function orderCargoDetails(){
        return $this->hasMany(OrderCargoDetail::class);
    }

}
