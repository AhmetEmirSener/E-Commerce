<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    public function refundItems(){
        return $this->hasMany(RefundItem::class);
    }
}
