<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    protected $fillable = [
        'order_id',
        'user_id', 
        'status',
        'reason',
        'cargo_tracking_code',
        'cargo_company',
        'shipped_at',
        'received_at',
        'admin_note',
    ];

    public function refundRequestItem(){
        return $this->hasMany(RefundRequestItem::class);
    }
}
