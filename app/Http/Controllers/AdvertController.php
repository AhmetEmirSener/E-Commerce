<?php

namespace App\Http\Controllers;

use App\Models\Advert;
use App\Models\Product;
use App\Models\Category;

use Illuminate\Http\Request;
use App\Http\Requests\AdvertRequest;
use App\Http\Requests\UpdateAdvertRequest;

use App\Http\Resources\AdvertResource;
use App\Http\Resources\miniAdvertResource;

use Illuminate\Support\Facades\DB;

use App\Services\SlugCreateService;

use App\Services\CategoryService;
use App\Http\Resources\ReviewStatsResource;
use App\Services\StatsCountService;
use App\Services\AdvertService;

class AdvertController extends Controller
{
    
    protected SlugCreateService $slugCreateService;
    protected CategoryService $categoryService;
    protected StatsCountService $statsService;

    protected AdvertService $advertService;

    public function __construct(SlugCreateService $slugCreateService, CategoryService $categoryService,
     StatsCountService $statsService, AdvertService $advertService){
        $this->slugCreateService=$slugCreateService;
        $this->categoryService=$categoryService;
        $this->statsService= $statsService;
        $this->advertService= $advertService;

    }

 

    public function getAdvert($slug){
        try {
            $advert = $this->advertService->getBySlug($slug);
            
            if(!$advert){
                return response()->json('Ürün bulunamadı',404);
            }
            $stats = $this->statsService->stats($advert->id,\App\Models\Review::class);
            
            $path = $this->categoryService->breadcrumb($advert->category);
            
            return response()->json([
                'data'=>[
                    'advert'=>new AdvertResource($advert),
                    'bread_crumb'=>$path,
                    'active_stock'=>$advert->product->stock>0,
                ],
                'stats'=>new ReviewStatsResource($stats),        
            ]);            
        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()],500);
        }
    }



    public function search(Request $request){
        $adverts = $this->advertService->search($request->q, $request->sort_by, $request->order,
        $request->min_price,$request->max_price);


        return response()->json([
            'data'=>miniAdvertResource::collection($adverts),
            'meta' => [
                'previous_page'=>$adverts->previousPageUrl(),
                'current_page' => $adverts->currentPage(),
                'last_page' => $adverts->lastPage(),

                'total' => $adverts->total(),
                ]
        ]);

    }


    public function quickSearch(Request $request){

        $adverts = $this->advertService->quickSearch($request->q);

        return miniAdvertResource::collection($adverts);
    }
    



    /* filament ile yapıldı ilerisi için adminadvert service kullan */
    public function createAdvert(AdvertRequest $request){

        try {
            $data = $request->validated();
            $product= Product::findOrFail($data['product_id']);
            $data['category_id']=$product->category_id;
            $data['slug']=$this->slugCreateService->createSlug($data,\App\Models\Advert::class);


            if(Advert::where('product_id',$product->id)->exists()){
                return response()->json(['message'=>'Ürün için ilan oluşturulmuş'],400);

            }

            $advert=Advert::create($data);

            return response()->json(['message'=>'İlan oluşturuldu','advert'=>$advert],201);

        } catch (\Exception $e) {
             return response()->json(['error'=>$e->getMessage()],500);

        }
    }
    public function updateAdvert(UpdateAdvertRequest $request,$id){
        try {
            $data = $request->validated();

            $advert = Advert::findOrFail($id);

            $advert->update($data);
            return response()->json(['message'=>'Güncelleme başarılı.','advert'=>$advert],200);

        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);

        }
    }

    public function getAdverts(){
        try {
            $adverts = Advert::with('product')->orderBy('created_at','desc')->get();
            return response()->json(['adverts'=>$adverts],200);

        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }

    public function deleteAdvert($id){
        try {
            $advert = Advert::findOrFail($id);
            $advert->delete();
            return response()->json(['message'=>'Ürün silindi.'],200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error'=>'İlan bulunamadı.'],404);
        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }
    /* filament ile yapıldı ilerisi için adminadvert service kullan */



}
