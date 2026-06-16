<?php

namespace App\Services;
use App\Models\Advert;
use App\Models\Product;

use Illuminate\Support\Facades\DB;

class AdvertService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }


    public function getBySlug(string $slug){
        $advert = Advert::where('slug',$slug)->with(['product','product.images','product.activeDiscount',
            'category',
            'reviews' => function($query) {
                $query->latest()->take(6);
            },'reviews.user:id,name,surname'
        ])->first();
        return $advert;
    }
    
    public function getForCartBySlug(string $slug){
        return Advert::where('slug',$slug)
            ->with('product.activeDiscount')
            ->first();
    }


    public function search(string $search, string $sort_by=null, string $order=null,
    float $min_price =null, float $max_price=null ){

        $allowedAdvert = ['avg_rating'];

        $allowedProduct = ['price'];

        return Advert::with('product.activeDiscount')->where(function ($query) use ($search){
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
            in_array($sort_by, $allowedAdvert),
            fn ($q) => $q->orderBy($sort_by, $order ?? 'desc')
        )
        ->when(
            in_array($sort_by, $allowedProduct),
            fn ($q) => $q->orderBy('effective_price', $order ?? 'asc')
        )
        ->when($min_price !== null || $max_price !== null,
            function($q) use ($min_price, $max_price){

                if($min_price && $max_price){
                    $q->havingRaw('effective_price BETWEEN ? AND ?',[
                        $min_price,
                        $max_price
                    ]);             
                }
                elseif($min_price){
                    $q->havingRaw('effective_price >= ?', [$min_price]);
                }elseif($max_price){
                    $q->havingRaw('effective_price <= ?', [$max_price]);
                }
            }
        
        )
            
        ->paginate(12);

    }

    public function quickSearch(string $search){
        return Advert::with('product.activeDiscount')->where(function ($query) use ($search){
            $query->where('title','LIKE',"%$search%")
            ->orWhere('description','LIKE',"%$search%");
        })
        ->orWhereHas('product', function ($query) use ($search){
            $query->where('name','LIKE',"%$search%")
            ->orWhere('features','LIKE',"%$search%");
        })
        ->limit(10)
        ->get();
    }
}



