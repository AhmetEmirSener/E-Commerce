<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SliderItems;



class Slider extends Model
{
    protected $guarded = [];


    public function items(){
        return $this->hasMany(SliderItems::class)->where('is_active',true)->orderBy('sort');
    }

}
