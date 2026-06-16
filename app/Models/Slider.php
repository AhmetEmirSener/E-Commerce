<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SliderItems;

use Illuminate\Support\Facades\Cache;


class Slider extends Model
{
    protected $guarded = [];

    protected static function booted(){

        static::saved(function ($slider){
            Cache::forget("slider_{$slider->id}");
            Cache::forget("layout_{$slider->page}");
        });

        static::deleted(function ($slider){
            Cache::forget("slider_{$slider->id}");
            Cache::forget("layout_{$slider->page}");
        });
    }


    public function items(){
        return $this->hasMany(SliderItems::class)->where('is_active',true)->orderBy('sort');
    }

}
