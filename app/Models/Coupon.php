<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{

    public function rules(){
        return $this->hasMany(CouponRule::class);
    }
    
    public function coupon_usage(){
        return $this->hasMany(CouponUsage::class);
    }



}
