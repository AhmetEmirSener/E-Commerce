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

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
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
        )->orderByDesc('adverts.views')->limit(10);
    }



    public function popularAdvertsWithChildren()
    {
        $this->loadMissing('getChild.getChild');    
        $childIds = $this->getAllChildrenIds();

        $categoryIds = collect($childIds)->push($this->id);
    
        return Advert::whereHas('product', function ($q) use ($categoryIds) {
            $q->whereIn('category_id', $categoryIds);
        })
        ->selectRaw('adverts.*,(avg_rating*20 + views) as score' )
        ->orderByDesc('score')
        ->limit(10);
    }

    public function getAllChildrenIds(&$ids=[]){
        foreach($this->getChild as $child){
            $ids[]=$child->id;

            if($child->getChild->isNotEmpty()){
                $child->getAllChildrenIds($ids);
            }
        }
        return $ids;

    }

}
