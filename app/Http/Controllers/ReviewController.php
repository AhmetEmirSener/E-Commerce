<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Advert;

use Illuminate\Http\Request;

use App\Http\Requests\ReviewRequest;
use App\Http\Resources\ReviewResource;

use App\Http\Resources\miniAdvertResource;

use App\Http\Resources\ReviewStatsResource;

use App\Services\ReviewService;
use App\Services\StatsCountService;

use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    
    protected ReviewService $reviewService;
    protected StatsCountService $statsService;

    public function __construct(ReviewService $reviewService, StatsCountService $statsService){
        $this->reviewService= $reviewService;
        $this->statsService= $statsService;
    }
    
    public function storeReview(ReviewRequest $request){
        try {
            $data = $request->validated();
            $data['user_id']=8; //  simdilik
            // doğrulama için serviceye geç şimdilik kalsın
            DB::transaction(function () use ($data) {
                $advert = Advert::lockForUpdate()->findOrFail($data['advert_id']);
                Review::create($data);
    
                $advert->rating_sum = $advert->rating_sum + $data['rating'];
                //$advert->increment('rating_sum',$data['rating']);
                //$advert->increment('total_comments');
                $advert->total_comments+=1;
                $advert->avg_rating=$advert->rating_sum / $advert->total_comments;
                
                $advert->save();
            });
        
            return response()->json('DONE');
        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()],500);
        }
    }





    public function getReviewBySlug($slug){
        try {
            $advert = Advert::where('slug',$slug)->firstOrFail();

            $reviews = $advert->reviews()->where('status','Aktif')->with('user:id,name,surname')->paginate(6);
            
            $stats = $this->statsService->stats($advert->id,\App\Models\Review::class);


            return response()->json([
                'data'=>[
                    'reviews'=> ReviewResource::collection($reviews),
                    'stats'=> new ReviewStatsResource($stats),
                ],
                'meta' => [
                        'current_page' => $reviews->currentPage(),
                        'last_page' => $reviews->lastPage(),
                        'total' => $reviews->total(),
                ]
               
            ]);
        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()],500);
        }
    }


    public function reviewPage($slug){
        try {
            $advert = Advert::where('slug', $slug)->firstOrFail();

            $stats = $this->statsService->stats($advert->id,\App\Models\Review::class);
            return response()->json([
                'data'=>[
                    'advert'=> new miniAdvertResource($advert),
                    'stats'=> new ReviewStatsResource($stats),
                ]
                ]);
        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()],500);

        }
    }

    public function filterReview(Request $request){
        try {
            $query = Review::query();
            $allowedSorts = ['rating','created_at'];

            $advertId = Advert::where('slug',$request->slug)->value('id');
            if(!$advertId){
                return response()->json(['message'=>'Ürün bulunamadı'],404);
            }

            $query
            ->when($advertId, fn($q,$v)=> $q->where('advert_id',$v))
            ->when($request->rating, fn($q,$v)=> $q->where('rating',$v))
            ->when(
                in_array($request->sort_by,$allowedSorts),
                fn($q)=>$q->orderBy($request->sort_by,$request->order ?? 'desc')
            )
            ->with('user:id,name,surname');  
            $reviews= $query->paginate($request->per_page ?? 6);
            return ReviewResource::collection($reviews);
    
        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()]);
        }
    }

}

