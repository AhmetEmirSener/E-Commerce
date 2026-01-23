<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Advert;

class Category extends Model
{
    protected $guarded = [];

    public function getChild(){
        return $this->hasMany(category::class,'parent_id');
    }

    public function popularAdverts()
    {
        return $this->hasManyThrough(
            Advert::class,
            Product::class,
            'category_id',   // products.category_id
            'product_id',    // adverts.product_id
            'id',            // categories.id
            'id'             // products.id
        )->orderByDesc('views')->limit(6);
    }

}
