<?php

namespace App\Services;

use App\Models\Review;

class StatsCountService
{
    public function stats($advertId, $modelClass){
        $stats = $modelClass::selectRaw('
            COUNT(*) as total,
            COALESCE(AVG(rating),0) as avg,
            SUM(rating = 5) as five,
            SUM(rating = 4) as four,
            SUM(rating = 3) as three,
            SUM(rating = 2) as two,
            SUM(rating = 1) as one
        ')->where('advert_id',$advertId)->first();

        return $stats;
    }
}
