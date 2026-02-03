<?php

namespace App\Services;

use App\Models\Advert;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function store(array $data){
        DB::transaction(function () use ($data) {
            $advert = Advert::lockForUpdate()->findOrFail($data['advert_id']);

            Review::create($data);

            $advert->increment('rating_sum',$data['rating']);
            $advert->increment('total_comments');

            $advert->avg_rating=$advert->rating_sum / $advert->total_comments;
            $advert->save();
        });
    }
}
