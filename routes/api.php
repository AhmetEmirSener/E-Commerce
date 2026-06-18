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
use App\Http\Controllers\CampaignRulesController;
use App\Http\Controllers\SavedCardController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderCargoController;
use App\Http\Controllers\SupportRequestController;



Route::get('/me', function (Request $request) {
    return $request->user()->only('id','name','surname','role','phone_number','email');
})->middleware(AuthMiddleware::class);

//PRODUCT 
Route::get('/getProducts',[ProductController::class,'getProducts'])->middleware(AuthMiddleware::class);

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

Route::put('/user',[UserController::class,'update'])->middleware(AuthMiddleware::class)->middleware('throttle:3,60');


Route::post('/email/sendOtp',[UserController::class,'sendOtp'])->middleware(AuthMiddleware::class);

Route::post('/email/verifyOtp',[UserController::class,'confirmAndUpdateEmail'])->middleware(AuthMiddleware::class);


Route::patch('/password',[UserController::class,'changePassword'])->middleware(AuthMiddleware::class);


Route::get('/card/saved',[UserController::class,'savedCards'])->middleware(AuthMiddleware::class);

Route::put('/card/saved/{id}',[UserController::class,'updateToDefault'])->middleware(AuthMiddleware::class);

Route::get('/cart/count',[CartController::class,'cartCount'])->middleware(AuthMiddleware::class);

//USER

// USER REVIEWS 

Route::get('/review',[UserController::class,'usersReview'])->middleware(AuthMiddleware::class);

Route::delete('/review/{id}',[UserController::class,'deleteReview'])->middleware(AuthMiddleware::class);


// USER REVIEWS 

// USER RESET PASSWORD

Route::post('/resetPasswordOtp',[UserController::class,'resetPasswordOtp']);
Route::post('/verifyPasswordOtp',[UserController::class,'verifyPasswordOtp']);

Route::post('/resetPassword',[UserController::class,'resetPassword']);


//USER ADDRESS
Route::get('/addresses',[UserAddressController::class,'getAddress'])->middleware(AuthMiddleware::class);

Route::post('/addresses',[UserAddressController::class,'createAddress'])->middleware(AuthMiddleware::class);

Route::put('/addresses/{id}',[UserAddressController::class,'updateAddress'])->middleware(AuthMiddleware::class);

Route::patch('/addresses/{id}/default',[UserAddressController::class,'updateToDefault'])->middleware(AuthMiddleware::class);


Route::delete('/addresses/{id}',[UserAddressController::class,'deleteAddress'])->middleware(AuthMiddleware::class);


Route::get('/getDefaultAddress',[UserAddressController::class,'getDefaultAddress'])->middleware(AuthMiddleware::class);

//USER ADDRESS


//CART
Route::post('/storeCart',[CartController::class,'storeCart'])->middleware(AuthMiddleware::class);

Route::post('/deleteCart',[CartController::class,'deleteCart'])->middleware(AuthMiddleware::class);

Route::get('/getUsersCart',[CartController::class,'getUsersCart'])->middleware(AuthMiddleware::class);

Route::post('/changeSelected',[CartController::class,'changeSelected'])->middleware(AuthMiddleware::class);
//CART


// SUPPORT REQUEST 

Route::post('/supportRequest',[SupportRequestController::class,'createSupport'])->middleware('throttle:5,10');

// SUPPORT REQUEST 


//PAYMENT 
Route::post('/prepareOrder',[PaymentController::class,'prepareOrder'])->middleware(AuthMiddleware::class);

// Route::post('/preparePayment',[PaymentController::class,'preparePayment'])->middleware(AuthMiddleware::class);


Route::post('/payment/callback', [PaymentController::class, 'callback']);

Route::get('/payment/result/{token}',[PaymentController::class,'paymentResult'])->middleware(AuthMiddleware::class);

Route::post('/stripe/webhook',[StripeWebhookController::class,'handle']);



Route::post('/payment/charge',[PaymentController::class,'payWithCard'])->middleware(AuthMiddleware::class);

Route::post('/payment/charge/savedCard',[PaymentController::class,'payWithSavedCard'])->middleware(AuthMiddleware::class);

Route::post('/payment/charge/form',[PaymentController::class,'payment'])->middleware(AuthMiddleware::class);


//PAYMENT 



//INSTALLMENTS 

Route::post('/payment/installment',[InstallmentController::class,'getInstallments'])->middleware(AuthMiddleware::class);

//INSTALLMENTS 




//SAVED CARD 

Route::delete('/card/saved/{id}',[SavedCardController::class,'deleteSavedCard'])->middleware(AuthMiddleware::class);


//SAVED CARD 





// ORDERS 

Route::get('/orders',[OrderController::class,'orders'])->middleware(AuthMiddleware::class);

Route::get('/order/{id}',[OrderController::class,'order'])->middleware(AuthMiddleware::class);

Route::post('/order/cancel/{id}',[OrderController::class,'cancelOrder'])->middleware(AuthMiddleware::class)->middleware('throttle:3,60');


Route::post('/order/refund/{id}',[OrderController::class,'refundOrder'])->middleware(AuthMiddleware::class);



Route::get('/order/refundInfo/{id}',[OrderController::class,'orderRefundInfo'])->middleware(AuthMiddleware::class);

// ORDERS 


// USER ORDER REFUND REQUEST 
Route::post('/order/refundRequest/{id}',[UserController::class,'refundRequest'])->middleware(AuthMiddleware::class);
// USER ORDER REFUND REQUEST 


// BRAND 

Route::post('/createBrand',[BrandController::class,'createBrand']);

Route::put('/updateBrand/{id}',[BrandController::class,'updateBrand']);

Route::get('/getBrands',[BrandController::class,'getBrands']);



// ADVERT  REVIEW 

Route::post('/review',[ReviewController::class,'storeReview'])->middleware(AuthMiddleware::class);;

//Route::get('/getAdvertsReview/{AdvertId}',[ReviewController::class,'getAdvertsReview']);

Route::get('/getReviewBySlug/{slug}',[ReviewController::class,'getReviewBySlug']);

Route::get('/reviewPage/{slug}',[ReviewController::class,'reviewPage']);
Route::get('/filteredReview',[ReviewController::class,'filterReview']);


// ADVERT  REVIEW 

Route::get('/search',[AdvertController::class,'search']);

Route::get('/quickSearch',[AdvertController::class,'quickSearch']);




// CATEGORY 

Route::post('/createCategory',[CategoryController::class,'storeCategory']);

Route::get('/categories',[CategoryController::class,'getCategories']);

Route::get('/searchByCategory/{slug}',[CategoryController::class,'searchByCategory']);

Route::get('/getCategoryTree/{slug}',[CategoryController::class,'getCategoryTree']);


// CATEGORY 

// CARGO


Route::post('/cargo',[OrderCargoController::class,'createCargo']);



// CARGO


// SLIDER 
Route::post('createSlider',[SliderController::class,'store']);

Route::post('createSliderItem',[SliderItemsController::class,'store']);

Route::get('getLayout/{sliderName}',[SliderController::class,'getLayout']);

Route::get('getSliderItem/{id}',[SliderController::class,'getSlider']);

Route::get('popularAdverts/{slug}/',[SliderController::class,'popularAdvertsByCategory']);

Route::get('recoAdverts/{slug}',[SliderController::class,'recoAdvertsByFeatures']);

// CAMPAIGN

Route::post('createCampaign',[CampaignController::class,'createCampaign']);

Route::post('createRules',[CampaignRulesController::class,'createRules']);

Route::get('createCampaignProducts/{slug}',[CampaignRulesController::class,'createCampaignProducts']);

Route::get('attachProduct/{slug}/{advertId}',[CampaignRulesController::class,'attachProduct']);

Route::get('detachProduct/{slug}/{advertId}',[CampaignRulesController::class,'detachProduct']);



Route::post('createCampaignExclusives',[CampaignRulesController::class,'createCampaignExclusives']);

Route::get('getCampaignDetails/{slug}',[CampaignRulesController::class,'getCampaignDetails']);

Route::get('getCampaignAdverts/{slug}',[CampaignRulesController::class,'getCampaignAdverts']);



// ADMIN REFUND 
Route::post('admin/refund/{id}',[OrderController::class,'refund'])->middleware(AuthMiddleware::class);

Route::post('admin/refundCargo/{id}',[OrderController::class,'refundCargoDetails'])->middleware(AuthMiddleware::class);
