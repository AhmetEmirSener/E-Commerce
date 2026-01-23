<?php

namespace App\Models;
use App\Models\Category;
use App\Models\Advert;


use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    public function category(){
        return $this->belongsTo(Category::class);
    }

}
