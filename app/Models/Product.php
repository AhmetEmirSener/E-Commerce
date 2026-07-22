<?php

namespace App\Models;
use App\Models\Category;
use App\Models\Advert;
use App\Jobs\UpdateCampaignDiscountJob;

use App\Services\ProductService;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

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

        static::saving(function ($product){

            if ($product->isDirty('price') || $product->isDirty('vat_rate')) {
            
                $price = (float) $product->price;
                $rate = (float) $product->vat_rate;
    
                if ($price > 0 && $rate >= 0) {
 
                    $productService = app(ProductService::class);
                    
                    $product->vat_amount = $productService->calculateVatAmount($price, $rate);
                } else {
                    $product->vat_amount = 0;
                }
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


    public function calculatedPrice()
    {
        return $this->activeDiscount ? $this->activeDiscount->discount_price : $this->price;
    }
  
}
