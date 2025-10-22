<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;

class BrandController extends Controller
{
    public function createBrand(StoreBrandRequest $req){
        try {
            $data= $req->validated();
            $slug = Str::slug($data['name']);
            $slugCount= Brand::where('slug','LIKE',"{$slug}%")->count();
            if($slugCount){
                $slug.='-'.($slugCount+1);
            }
            $brand=Brand::create([...$data,'slug'=>$slug]);
            return response()->json(['message'=>'Marka oluşturuldu',$brand]);

        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }


    public function updateBrand(UpdateBrandRequest $req,$id ){
        try {
            $brand = Brand::findOrFail($id);
            $data = $req->validated();

            if (empty($data)) {
                return response()->json(['message' => 'Güncellenecek veri bulunamadı.'], 400);
            }

            if(!empty($data['name'])){
                $nameCount = Brand::where('name',$data['name'])->where('id','!=',$brand->id)->count();
                if($nameCount>0) return response()->json(['message'=>'Marka adı zaten kayıtlı'],400);
            }

            if (!empty($data['name']) && $brand->name !== $data['name']) {
                $slug = Str::slug($data['name']);
                $slugCount = Brand::where('slug', 'LIKE', "{$slug}%")
                                  ->where('id', '!=', $brand->id)
                                  ->count();
    
                if ($slugCount) {
                    $slug .= '-' . ($slugCount + 1);
                }
    
                $data['slug'] = $slug;
            }
            
            $brand->update($data);
            return response()->json(['message'=>'Marka güncellendi','brand'=>$brand]);

        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }


    public function getBrands(){
        try {
            $brands = Brand::where('status','Aktif')->get();

            return response()->json(['data'=>$brands],200);

        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }
}
