<?php

namespace App\Services;
use App\Models\ProductImage;

class ImageUploadService
{
   public function imageUpload($files,$product,$data=[]){



        $sort=1;
        $isFirst = true;
        $mainImagePath = null;

        foreach($files as $file){
            $path= $file->store('uploads','public');

            if($isFirst){
                $mainImagePath=$path;
            }
            ProductImage::create([
                'product_id'=>$product->id,
                'path'=>$path,
                'title'=>$data['title'] ?? null,
                'sort'=>$sort,
                'is_main'=>$isFirst ? 1:0
            ]);
            $isFirst = false;
            $sort++;
        }
        
        if($mainImagePath){
            $product->update([
                'image'=>$mainImagePath
            ]);
        }
    }

   
}
