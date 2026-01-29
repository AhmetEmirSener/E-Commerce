<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Advert;

use Illuminate\Http\Request;

use App\Http\Requests\ReviewRequest;
use App\Http\Resources\ReviewResource;



class ReviewController extends Controller
{
    

    public function storeReview(ReviewRequest $request){
        try {
            $data = $request->validated();
            $data['user_id']=2; // 
            Review::create($data);
            return response()->json('DONE');
        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()],500);
        }
    }


    public function getAdvertsReview($advertId){
        try {

            $advert = Advert::findOrFail($advertId);
            $review= $advert->reviews()
            ->where('status','Aktif')
            ->with('user')
            ->latest()
            ->paginate(5);
            return ReviewResource::collection($review);

        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()],500);
        }
    }


}
