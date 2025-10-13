<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AdvertController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//PRODUCT 
Route::get('/getProducts',[ProductController::class,'getProducts']);

Route::post('/storeProduct',[ProductController::class,'createProduct']);

Route::put('/updateProduct/{id}',[ProductController::class,'updateProduct']);

Route::delete('/deleteProduct/{id}',[ProductController::class,'deleteProduct']);
//PRODUCT 

//ADVERT
Route::post('/storeAdvert',[AdvertController::class,'createAdvert']);

Route::put('/updateAdvert/{id}',[AdvertController::class,'updateAdvert']);

Route::get('/getAdverts',[AdvertController::class,'getAdverts']);

Route::delete('/deleteAdvert/{id}',[AdvertController::class,'deleteAdvert']);
