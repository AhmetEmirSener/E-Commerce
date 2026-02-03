<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;


class Advert extends Model
{
    protected $guarded = [];

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function reviews(){
        return $this->hasMany(Review::class);
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }

}
