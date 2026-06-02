<?php

namespace App\Services;

use App\Models\Advert;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function approve(Review $review)
    {
      if ($review->approved_at !== null) return;

        DB::transaction(function () use ($review) {
            // İlanı kilitleyip çekiyoruz kanka
            $advert = $review->advert; // Review modelinde advert() ilişkisi olduğunu varsayıyorum
            if (!$advert) return;

            $advert->lockForUpdate();

            // 1. Yorumun kendisini onaylıyoruz
            $review->update(['approved_at' => now()]);

            // 2. İstatistikleri sadece ONAY ANINDA arttırıyoruz mq!
            $advert->increment('rating_sum', $review->rating);
            $advert->increment('total_comments');

            // 3. Yeni ortalamayı basıyoruz kanka
            $advert->avg_rating = $advert->rating_sum / $advert->total_comments;
            $advert->save();
        });
    }
}
