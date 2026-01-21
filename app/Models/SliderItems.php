<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use App\Models\Advert;
use App\Models\Campaign;

class SliderItems extends Model
{
    protected $guarded = [];

    
    public function get_category(){
        return $this->belongsTo(Category::class);
    }

    public function get_product(){
        return $this->belongsTo(Product::class);
    }

    public function advert()
    {
        return $this->belongsTo(Advert::class, 'ref_id');
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class, 'ref_id');
    }

    public function campaign(){
        return $this->belongsTo(Campaign::class,'ref_id');
    }
}
