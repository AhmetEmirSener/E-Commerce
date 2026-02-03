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
use App\Http\Controllers\StripeWebhookController;

use App\Http\Controllers\SliderItemsController;
use App\Http\Controllers\SliderController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ReviewController;

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
Route::get('/advert/{slug}',[AdvertController::class,'getAdvert']);

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
Route::get('/prepareOrder',[PaymentController::class,'prepareOrder'])->middleware(AuthMiddleware::class);

Route::post('/preparePayment',[PaymentController::class,'preparePayment'])->middleware(AuthMiddleware::class);


Route::post('/stripe/webhook',[StripeWebhookController::class,'handle']);

// BRAND 

Route::post('/createBrand',[BrandController::class,'createBrand']);

Route::put('/updateBrand/{id}',[BrandController::class,'updateBrand']);

Route::get('/getBrands',[BrandController::class,'getBrands']);



// REVIEW 

Route::post('/storeReview',[ReviewController::class,'storeReview']);

Route::get('/getAdvertsReview/{AdvertId}',[ReviewController::class,'getAdvertsReview']);

Route::get('/getReviewBySlug/{slug}',[ReviewController::class,'getReviewBySlug']);


// CATEGORY 

Route::post('/createCategory',[CategoryController::class,'storeCategory']);

Route::get('/getCategories',[CategoryController::class,'getCategories']);

// SLIDER 
Route::post('createSlider',[SliderController::class,'store']);

Route::post('createSliderItem',[SliderItemsController::class,'store']);


Route::get('getSliderItem/{name}',[SliderController::class,'getSlider']);

Route::get('popularAdverts/{advertId}/',[SliderController::class,'popularAdvertsByCategory']);

Route::get('recoAdverts/{productId}',[SliderController::class,'recoAdvertsByFeatures']);

// CAMPAIGN

Route::post('createCampaign',[CampaignController::class,'createCampaign']);

