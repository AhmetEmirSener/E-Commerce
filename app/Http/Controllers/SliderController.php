<?php

namespace App\Http\Controllers;

use App\Models\Slider;
use App\Models\SliderItems;

use App\Models\Category;
use App\Models\Product;
use App\Models\Advert;

use Illuminate\Http\Request;
use App\Http\Requests\StoreSlider;
use App\Http\Resources\SliderResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\AdvertResource;
use App\Http\Resources\popularAdvertsByCate;
use App\Http\Resources\MiniAdvertResource;

use App\Services\CategoryService;


class SliderController extends Controller
{

    protected CategoryService $categoryService;
    
    public function __construct(CategoryService $categoryService){
        $this->categoryService=$categoryService;
    }


    public function store(StoreSlider $request){
        try {
            $data = $request->validated();

            $slider = Slider::create($data);

            return response()->json(['Message'=>'Slider oluşturuldu.',$slider],200);
        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }


    public function getLayout($sliderName){
        try {
            $layout = Slider::where('page',$sliderName)->orderBy('sort')->get(['id','type', 'sort']);


            return response()->json($layout);

        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()],500);
        }
    }

    
    public function getSlider($sliderId){
        try {
            $slider = Slider::with([
                'items',
                'items.advert.product',
                'items.category',
                'items.campaign'
            ])->findOrFail($sliderId);
            
            return new SliderResource($slider);
            /* 
            return response()->json($slider);


            if($sliderName==='advertPage' && $request->category_id){
                $popularAdverts = Category::where('id',$request->category_id)->with('popularAdverts')->get();
                return response()->json($popularAdverts);
            }

            $slider = Slider::where('page',$sliderName)->with([
                'items',
                'items.advert.product',
                'items.category',
                'items.campaign'
            ])->get()->sortBy('sort');

            if(empty($slider)){
                return response()->json(['message'=>'Slider bulunamadı'],400);
            }
            

            
            return SliderResource::collection($slider);  
            */

        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }

    public function popularAdvertsByCategory($slug){
        try {
            $advert = Advert::with('category')->where('slug',$slug)->first();

            
            $path = $this->categoryService->breadcrumb($advert->category);
            $productTypeCategory = $path[1] ?? $advert->category;

            $cate = Category::with('getChild')->findOrFail($productTypeCategory['id']);
            $cate->popular_adverts = $cate->popularAdvertsWithChildren()
            ->where('adverts.id','!=',$advert->id)->get();

            return MiniAdvertResource::collection($cate->popular_adverts);

        }catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }


    public function recoAdvertsByFeatures($slug){
        try {
            $advert = Advert::where('slug',$slug)->first();
            $product = Product::findOrFail($advert->product_id);
            $features = collect($product->features)->pluck('key')->toArray();
            if(!$features){
                return response()->json([]);
            }
            $adverts  = Advert::where('id','!=',$advert->id)->whereHas('product', function($q) use($advert,$features){

                $q->where(function($qq) use ($features){
                    foreach($features as $f){
                        $qq->orWhereJsonContains('features',['key'=>$f]);
                    }
                });
            })   
            ->with('product')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

            return MiniAdvertResource::collection($adverts);

        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()],500);

        }
    }

    /*
    public function recoAdvertsByFeatures($productId){
    try {
        $product = Product::findOrFail($productId);
        $advert = Advert::where('product_id',$product->id)->firstOrFail();
        $features = collect($product->features)->pluck('key')->toArray();

        if(empty($features)){
            return response()->json([]);
        }

        $featureScoreSql = [];
        foreach ($features as $f) {
            $featureScoreSql[] = "JSON_CONTAINS(products.features, JSON_OBJECT('key', '$f'))";
        }

        $scoreSql = implode(' + ', $featureScoreSql);

        $adverts = Advert::selectRaw("
                adverts.*,
                ($scoreSql) as match_score,
                ((products.category_id = ?) * 2) as category_score,
                ((($scoreSql) * 5) + ((products.category_id = ?) * 2) + (adverts.views / 50)) as total_score
            ", [$advert->product->category_id, $advert->product->category_id])
            ->join('products', 'products.id', '=', 'adverts.product_id')
            ->where('adverts.id','!=',$advert->id)
            ->having('match_score','>',0)
            ->orderByDesc('total_score')
            ->limit(10)
            ->get();

        return MiniAdvertResource::collection($adverts);

    } catch (\Throwable $th) {
        return response()->json(['message'=>$th->getMessage()],500);
    }
}
    */


}
