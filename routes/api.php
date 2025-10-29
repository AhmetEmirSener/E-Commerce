<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AdvertController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserOtpController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\BrandController;
use App\Http\Middleware\AuthMiddleware;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//PRODUCT 
Route::get('/getProducts',[ProductController::class,'getProducts'])->middleware(AuthMiddleware::class);

Route::post('/storeProduct',[ProductController::class,'createProduct']);

Route::put('/updateProduct/{id}',[ProductController::class,'updateProduct']);

Route::delete('/deleteProduct/{id}',[ProductController::class,'deleteProduct']);
//PRODUCT 

//ADVERT
Route::post('/storeAdvert',[AdvertController::class,'createAdvert']);

Route::put('/updateAdvert/{id}',[AdvertController::class,'updateAdvert']);

Route::get('/getAdverts',[AdvertController::class,'getAdverts']);

Route::delete('/deleteAdvert/{id}',[AdvertController::class,'deleteAdvert']);
//ADVERT

//USER
Route::post('/register',[UserController::class,'register']);
Route::post('/sendOtp',[UserOtpController::class,'sendOtp']);
Route::post('/verifyOtp',[UserOtpController::class,'verifyOtp']);

Route::post('/login',[UserController::class,'login']);

Route::post('/logout',[UserController::class,'logout']);
//USER

//USER ADDRESS
Route::post('/createAddress',[UserAddressController::class,'createAddress'])->middleware('auth:sanctum');

Route::put('/updateAddress/{id}',[UserAddressController::class,'updateAddress'])->middleware('auth:sanctum');

Route::delete('/deleteAddress/{id}',[UserAddressController::class,'deleteAddress'])->middleware('auth:sanctum');

Route::get('/getAddress',[UserAddressController::class,'getAddress'])->middleware('auth:sanctum');
//USER ADDRESS



//CART
Route::post('/storeCart',[CartController::class,'storeCart'])->middleware('auth:sanctum');

Route::post('/deleteCart',[CartController::class,'deleteCart'])->middleware('auth:sanctum');

Route::get('/getUsersCart',[CartController::class,'getUsersCart'])->middleware('auth:sanctum');
//CART


//Check before order
Route::get('/prepareOrder',[PaymentController::class,'prepareOrder'])->middleware('auth:sanctum');

Route::post('/preparePayment',[PaymentController::class,'preparePayment'])->middleware('auth:sanctum');




// BRAND 

Route::post('/createBrand',[BrandController::class,'createBrand']);

Route::put('/updateBrand/{id}',[BrandController::class,'updateBrand']);

Route::get('/getBrands',[BrandController::class,'getBrands']);




