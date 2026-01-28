<?php

namespace App\Models;
use App\Models\Category;
use App\Models\Advert;


use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];
    protected $casts = [
        'features' => 'array',
    ];
    

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function images(){
        return $this->hasMany(ProductImage::class);
    }
}
