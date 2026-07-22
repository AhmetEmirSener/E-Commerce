<?php

namespace App\Services;

class ProductService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function calculateVatAmount(float $price, float $vat_rate){
        $basePrice = $price / (($vat_rate + 100) / 100);

        $vatAmount = $price - $basePrice;


        return round($vatAmount,2);

    }
}
