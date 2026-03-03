<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $guarded = [];


    public function rules(){
        return $this->hasMany(CampaignRules::class);
    }
    public function activeProductDiscounts(){
        return $this->hasMany(ProductDiscount::class,'campaign_id')
            ->where('is_active',1)
            ->with('product.advert');
    }
}
