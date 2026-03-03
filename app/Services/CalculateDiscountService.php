<?php

namespace App\Services;

class CalculateDiscountService
{

    
    public function calculateDiscount($price,$campaign){
        if($campaign->discount_type=='percent'){
            $discounted = $price - ($price * $campaign->discount_value / 100);
            return max(0, $discounted);

        }
        if($campaign->discount_type == 'fixed'){
            return max(0, $price - $campaign->discount_value);
        }
        return $price;
    }
}
