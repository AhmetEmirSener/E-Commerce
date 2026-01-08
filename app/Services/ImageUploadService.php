<?php

namespace App\Services;
use App\Models\ProductImage;

class ImageUploadService
{
   public function imageUpload($request,$product,$data=[]){


    if($request->hasFile('image')){
        $sort=1;
        $isFirst = true;
        foreach($request->file('image') as $file){
            $path= $file->store('uploads','public');

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
    }

   }
}
