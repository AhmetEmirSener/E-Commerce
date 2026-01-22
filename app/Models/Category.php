<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = [];

    public function getChild(){
        return $this->hasMany(category::class,'parent_id');
    }

}
