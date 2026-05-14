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

class AdvertController extends Controller
{
    
    protected SlugCreateService $slugCreateService;
    protected CategoryService $categoryService;
    protected StatsCountService $statsService;


    public function __construct(SlugCreateService $slugCreateService, CategoryService $categoryService, StatsCountService $statsService){
        $this->slugCreateService=$slugCreateService;
        $this->categoryService=$categoryService;
        $this->statsService= $statsService;

    }

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

    public function getAdvert($slug){
        try {
            $advert = Advert::where('slug',$slug)->with(['product','product.images','product.activeDiscount',
            'reviews' => function($query) {
                $query->latest()->take(6);
            },'reviews.user:id,name,surname'
            ])->first();

            if(!$advert){
                return response()->json('Ürün bulunamadı',404);
            }
            $category = Category::findOrFail($advert->category_id);
            $stats = $this->statsService->stats($advert->id,\App\Models\Review::class);

            $path = $this->categoryService->breadcrumb($category);
            $noneStock = $advert->product->stock>0;
            if($advert){
                return response()->json([
                    'data'=>[
                        'advert'=>new AdvertResource($advert),
                        'bread_crumb'=>$path,
                        'active_stock'=>$noneStock,
                    ],
                    'stats'=>new ReviewStatsResource($stats),

                    
                ]);
            }
            return response()->json('Ürün bulunamadı',404);
        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()],500);
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


    public function search(Request $request){
        $search = $request->q;
        $allowedAdvert = ['avg_rating'];

        $allowedProduct = ['price'];

        $adverts = Advert::with('product.activeDiscount')->where(function ($query) use ($search){
            $query->where('title','LIKE',"%$search%")
            ->orWhere('description','LIKE',"%$search%");
        })
        ->orWhereHas('product', function ($query) use ($search){
            $query->where('name','LIKE',"%$search%")
            ->orWhere('features','LIKE',"%$search%");
        })
        ->addSelect([
            'effective_price'=>Product::select(
                DB::raw('COALESCE(d.discount_price, products.price)')
            )
            ->join('adverts as a', 'a.product_id', '=', 'products.id')
            ->leftJoin('product_discounts as d', function($join){
                $join->on('d.product_id', '=', 'products.id')
                ->where('d.is_active', 1);
            })
            ->whereColumn('products.id', 'adverts.product_id')
            ->limit(1)
        ])
        ->when(
            in_array($request->sort_by, $allowedAdvert),
            fn ($q) => $q->orderBy($request->sort_by, $request->order ?? 'desc')
        )
        ->when(
            in_array($request->sort_by, $allowedProduct),
            fn ($q) => $q->orderBy('effective_price', $request->order ?? 'asc')
        )
        ->when($request->filled('min_price') || $request->filled('max_price'),
            function($q) use ($request){

                if($request->filled('min_price') && $request->filled('max_price')){
                    $q->havingRaw('effective_price BETWEEN ? AND ?',[
                        $request->min_price,
                        $request->max_price
                    ]);             
                }
                elseif($request->filled('min_price')){
                    $q->havingRaw('effective_price >= ?', [$request->min_price]);
                }elseif($request->filled('max_price')){
                    $q->havingRaw('effective_price <= ?', [$request->max_price]);
                }
            }
        
        )
            
        ->paginate(12);

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
        $search = $request->q;

        $adverts = Advert::with('product.activeDiscount')->where(function ($query) use ($search){
            $query->where('title','LIKE',"%$search%")
            ->orWhere('description','LIKE',"%$search%");
        })
        ->orWhereHas('product', function ($query) use ($search){
            $query->where('name','LIKE',"%$search%")
            ->orWhere('features','LIKE',"%$search%");
        })
        ->limit(10)
        ->get();

        return miniAdvertResource::collection($adverts);


        
    }
    


}
