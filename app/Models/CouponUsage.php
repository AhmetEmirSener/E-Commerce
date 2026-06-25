<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model
{
    protected $guarded = [];

    public function coupon(){
        return $this->belongsTo(Coupon::class);
    }
}
