<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignAdverts extends Model
{
    protected $guarded = [];

    public function advert(){
        return $this->belongsTo(Advert::class);
    }
}
