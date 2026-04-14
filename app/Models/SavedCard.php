<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedCard extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'card_token',
        'card_user_key',
        'created_at',
        'updated_at',
    ];
}
