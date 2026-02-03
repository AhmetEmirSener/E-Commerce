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
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    
    protected ReviewService $reviewService;

    public function __construct(ReviewService $reviewService){
        $this->reviewService= $reviewService;
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
            $advert = Advert::where('slug',$slug)->select('id','product_id','title','avg_rating','total_comments','slug')->with('product:id,image')->firstOrFail();

            $reviews = $advert->reviews()->where('status','Aktif')->with('user:id,name,surname')->paginate(6);
            
            $stats = Review::selectRaw('
            COUNT(*) as total,
            AVG(rating) as avg,
            SUM(rating = 5) as five,
            SUM(rating = 4) as four,
            SUM(rating = 3) as three,
            SUM(rating = 2) as two,
            SUM(rating = 1) as one
            ')->where('advert_id',$advert->id)->first();

            return response()->json([
                'data'=>[
                    'advert'=> new miniAdvertResource($advert),
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

}
