<?php

namespace App\Services;
use Illuminate\Support\Facades\Cache;
use App\Models\Category;

class CategoryService
{
    public function breadcrumb($category):array{
        $path = [];
        $current =$category;
        while($current){
            $path[] = [
                'id' => $current->id,
                'name' => $current->name,
                'slug' => $current->slug,
            ];
            $current=$current->parent;
        }
        $path=array_reverse($path);
        return $path;
    }


    public function branchWithParentsAndChildren(Category $category)
    {
        // en üst parentı bul
        $root = $category;
    
        while ($root->parent) {
            $root = $root->parent;
        }
    
        // root'tan itibaren tüm subtree’yi yükle
        return Category::with('childrenRecursive')->find($root->id);
    }


/* for later */

    public function tree(){
        return Cache::rememberForever('categories.tree',function(){
            $categories= Category::all();
            return $this->buildTree($categories);
        });
    }

    private function buildTree($categories,$parentId=null){
        return $categories
        ->where('parent_id',$parentId)
        ->map(function ($cat) use ($categories){
            return[
                'id'=>$cat->id,
                'name'=>$cat->name,
                'slug'=>$cat->slug,
                'children'=>$this->buildTree($categories,$cat->id)
            ];
        })
        ->values();
    }
}
