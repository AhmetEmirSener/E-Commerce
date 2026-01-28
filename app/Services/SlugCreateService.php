<?php

namespace App\Services;
use Illuminate\Support\Str;
use App\Models\Product;

class SlugCreateService
{
   public function createSlug($data=[],string $modelClass,$ignoreId=null){

    $baseSlug = Str::slug($data['name'] ?? $data['title']);
    $slug = $baseSlug;

    $query= $modelClass::where('slug','LIKE',"{$slug}%");

    if($ignoreId){
        $query->where('id','!=',$ignoreId);
    }
    $latestSlug = $query->orderBy('slug','desc')->value('slug');

    
    if ($latestSlug) {
        preg_match('/-(\d+)$/', $latestSlug, $matches);
        $number = isset($matches[1]) ? (int)$matches[1] + 1 : 2;
        $slug = $baseSlug . '-' . $number;
    }
    return $slug;
   

    }
}
