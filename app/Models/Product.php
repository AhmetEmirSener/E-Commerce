<?php

namespace App\Models;
use App\Models\Category;
use App\Models\Advert;
use App\Jobs\UpdateCampaignDiscountJob;


use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];
    protected $casts = [
        'features' => 'array',
    ];

    protected static function booted(){
        static::updating(function($product){
            if($product->isDirty('price') && $product->is_campaign_on){
                UpdateCampaignDiscountJob::dispatch($product);
            }
        });
    }

    
    public function advert(){
        return $this->hasOne(Advert::class);
    }
    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function images(){
        return $this->hasMany(ProductImage::class);
    }

    public function activeDiscount(){
        return $this->hasOne(ProductDiscount::class)->where('is_active',1);
    }
}
