<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundRequestItem extends Model
{
    protected $fillable = [
        'refund_request_id',
        'order_item_id',
        'quantity',
        'amount',
    ];

    public function refundRequest(){
        return $this->belongsTo(RefundRequest::class);
    }
}
