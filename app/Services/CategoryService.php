<?php

namespace App\Services;

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
}
