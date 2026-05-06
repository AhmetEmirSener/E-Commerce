<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportRequest extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'contact_name',
        'contact_email',
        'contact_phone',
        'topic',
        'message',
        'contact_preference',
        'status',
    ];
}
