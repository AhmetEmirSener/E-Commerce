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
use App\Http\Controllers\CouponController;



Route::get('/me', function (Request $request) {
    return $request->user()->only('id','name','surname','role','phone_number','email');
})->middleware(AuthMiddleware::class);


//ADVERT
Route::get('/advert/{slug}',[AdvertController::class,'getAdvert'])->middleware('throttle:60,1');


//ADVERT

//USER
Route::post('/register',[UserController::class,'register'])->middleware('throttle:5,1');;
Route::post('/sendOtp',[UserOtpController::class,'sendOtp'])->middleware('throttle:3,1');
Route::post('/verifyOtp',[UserOtpController::class,'verifyOtp'])->middleware('throttle:5,1');

Route::post('/login',[UserController::class,'login'])->middleware('throttle:8,1');

Route::post('/logout',[UserController::class,'logout'])->middleware('throttle:60,1');

Route::put('/user',[UserController::class,'update'])->middleware(AuthMiddleware::class)->middleware('throttle:3,60');


Route::post('/email/sendOtp',[UserController::class,'sendOtp'])->middleware([AuthMiddleware::class, 'throttle:3,1']);;

Route::post('/email/verifyOtp',[UserController::class,'confirmAndUpdateEmail'])->middleware([AuthMiddleware::class, 'throttle:5,1']);


Route::patch('/password',[UserController::class,'changePassword'])->middleware([AuthMiddleware::class, 'throttle:3,1']);




//USER

// USER REVIEWS 

Route::get('/review',[UserController::class,'usersReview'])->middleware([AuthMiddleware::class, 'throttle:60,1']);

Route::delete('/review/{id}',[UserController::class,'deleteReview'])->middleware([AuthMiddleware::class, 'throttle:10,1']);


// USER REVIEWS 

// USER RESET PASSWORD

Route::post('/resetPasswordOtp',[UserController::class,'resetPasswordOtp'])->middleware('throttle:3,1');
Route::post('/verifyPasswordOtp',[UserController::class,'verifyPasswordOtp'])->middleware('throttle:5,1');

Route::post('/resetPassword',[UserController::class,'resetPassword'])->middleware('throttle:3,1');


//USER ADDRESS
Route::get('/addresses',[UserAddressController::class,'getAddress'])->middleware([AuthMiddleware::class, 'throttle:60,1']);

Route::get('/getDefaultAddress',[UserAddressController::class,'getDefaultAddress'])->middleware([AuthMiddleware::class, 'throttle:60,1']);


Route::post('/addresses',[UserAddressController::class,'createAddress'])->middleware([AuthMiddleware::class, 'throttle:10,1']);

Route::put('/addresses/{id}',[UserAddressController::class,'updateAddress'])->middleware([AuthMiddleware::class, 'throttle:15,1']);

Route::patch('/addresses/{id}/default',[UserAddressController::class,'updateToDefault'])->middleware([AuthMiddleware::class, 'throttle:15,1']);


Route::delete('/addresses/{id}',[UserAddressController::class,'deleteAddress'])->middleware([AuthMiddleware::class, 'throttle:15,1']);



//USER ADDRESS


//CART
Route::post('/storeCart',[CartController::class,'storeCart'])->middleware([AuthMiddleware::class, 'throttle:30,1']);

Route::post('/deleteCart',[CartController::class,'deleteCart'])->middleware([AuthMiddleware::class, 'throttle:30,1']);

Route::get('/getCart',[CartController::class,'getCart'])->middleware([AuthMiddleware::class, 'throttle:60,1']);

Route::get('/checkout/cart',[CartController::class,'checkoutCart'])->middleware([AuthMiddleware::class, 'throttle:60,1']);

Route::get('/cart/count',[CartController::class,'cartCount'])->middleware(AuthMiddleware::class)->middleware([AuthMiddleware::class, 'throttle:60,1']);


Route::post('/changeSelected',[CartController::class,'changeSelected'])->middleware([AuthMiddleware::class, 'throttle:30,1']);
//CART


// SUPPORT REQUEST 

Route::post('/supportRequest',[SupportRequestController::class,'createSupport'])->middleware('throttle:5,10');

// SUPPORT REQUEST 


//PAYMENT 
Route::post('/prepareOrder',[PaymentController::class,'prepareOrder'])->middleware([AuthMiddleware::class, 'throttle:10,1']);

// Route::post('/preparePayment',[PaymentController::class,'preparePayment'])->middleware(AuthMiddleware::class);


Route::post('/payment/callback', [PaymentController::class, 'callback'])->middleware('throttle:120,1'); // dikkat et ilerleyen zamanlarda!!!!!

Route::get('/payment/result/{token}',[PaymentController::class,'paymentResult'])->middleware(AuthMiddleware::class)->middleware('throttle:15,1');


//   ******** Hazır paymentler***** 
// Route::post('/payment/charge',[PaymentController::class,'payWithCard'])->middleware(AuthMiddleware::class);

// Route::post('/payment/charge/savedCard',[PaymentController::class,'payWithSavedCard'])->middleware(AuthMiddleware::class);
//   ******** Hazır paymentler***** 


Route::post('/payment/charge/form',[PaymentController::class,'payment'])->middleware([AuthMiddleware::class, 'throttle:3,1']);


//PAYMENT 



//INSTALLMENTS 

// Route::post('/payment/installment',[InstallmentController::class,'getInstallments'])->middleware(AuthMiddleware::class);

//INSTALLMENTS 




//SAVED CARD 
/*
Route::delete('/card/saved/{id}',[SavedCardController::class,'deleteSavedCard'])->middleware(AuthMiddleware::class);

Route::get('/card/saved',[UserController::class,'savedCards'])->middleware(AuthMiddleware::class);

Route::put('/card/saved/{id}',[UserController::class,'updateToDefault'])->middleware(AuthMiddleware::class);

*/

//SAVED CARD 





// ORDERS 

Route::get('/orders',[OrderController::class,'orders'])->middleware([AuthMiddleware::class, 'throttle:60,1']);

Route::get('/order/{id}',[OrderController::class,'order'])->middleware([AuthMiddleware::class, 'throttle:60,1']);

Route::post('/order/cancel/{id}',[OrderController::class,'cancelOrder'])->middleware(AuthMiddleware::class)->middleware('throttle:3,60');


Route::post('/order/refund/{id}',[OrderController::class,'refundOrder'])->middleware([AuthMiddleware::class, 'throttle:3,60']);



Route::get('/order/refundInfo/{id}',[OrderController::class,'orderRefundInfo'])->middleware([AuthMiddleware::class, 'throttle:60,1']);

// ORDERS 


// USER ORDER REFUND REQUEST 
Route::post('/order/refundRequest/{id}',[UserController::class,'refundRequest'])->middleware(AuthMiddleware::class)->middleware('throttle:3,60');
// USER ORDER REFUND REQUEST 


// BRAND 
/*
Route::post('/createBrand',[BrandController::class,'createBrand']);

Route::put('/updateBrand/{id}',[BrandController::class,'updateBrand']);

Route::get('/getBrands',[BrandController::class,'getBrands']);
*/


// ADVERT  REVIEW 

Route::post('/review',[ReviewController::class,'storeReview'])->middleware([AuthMiddleware::class, 'throttle:5,1']);

//Route::get('/getAdvertsReview/{AdvertId}',[ReviewController::class,'getAdvertsReview']);

Route::get('/getReviewBySlug/{slug}',[ReviewController::class,'getReviewBySlug'])->middleware('throttle:60,1');

Route::get('/reviewPage/{slug}',[ReviewController::class,'reviewPage'])->middleware('throttle:60,1');
Route::get('/filteredReview',[ReviewController::class,'filterReview'])->middleware('throttle:60,1');


// ADVERT  REVIEW 

Route::get('/search',[AdvertController::class,'search'])->middleware('throttle:60,1');

Route::get('/quickSearch',[AdvertController::class,'quickSearch'])->middleware('throttle:60,1');




// CATEGORY 
/*
Route::post('/createCategory',[CategoryController::class,'storeCategory']);

*/
Route::get('/categories',[CategoryController::class,'getCategories'])->middleware('throttle:120,1');


Route::get('/searchByCategory/{slug}',[CategoryController::class,'searchByCategory'])->middleware('throttle:60,1');

Route::get('/getCategoryTree/{slug}',[CategoryController::class,'getCategoryTree'])->middleware('throttle:60,1');


// CATEGORY 

// CARGO


// Route::post('/cargo',[OrderCargoController::class,'createCargo']);



// CARGO


// SLIDER 
//Route::post('createSlider',[SliderController::class,'store']);

//Route::post('createSliderItem',[SliderItemsController::class,'store']);

Route::get('getLayout/{sliderName}',[SliderController::class,'getLayout'])->middleware('throttle:60,1');

Route::get('getSliderItem/{id}',[SliderController::class,'getSlider'])->middleware('throttle:60,1');

Route::get('popularAdverts/{slug}/',[SliderController::class,'popularAdvertsByCategory'])->middleware('throttle:60,1');

Route::get('recoAdverts/{slug}',[SliderController::class,'recoAdvertsByFeatures'])->middleware('throttle:60,1');

// CAMPAIGN
/*
Route::post('createCampaign',[CampaignController::class,'createCampaign']);

Route::post('createRules',[CampaignRulesController::class,'createRules']);

Route::get('createCampaignProducts/{slug}',[CampaignRulesController::class,'createCampaignProducts']);

Route::get('attachProduct/{slug}/{advertId}',[CampaignRulesController::class,'attachProduct']);

Route::get('detachProduct/{slug}/{advertId}',[CampaignRulesController::class,'detachProduct']);
*/



// Route::post('createCampaignExclusives',[CampaignRulesController::class,'createCampaignExclusives']);

Route::get('getCampaignDetails/{slug}',[CampaignRulesController::class,'getCampaignDetails'])->middleware('throttle:60,1');

Route::get('getCampaignAdverts/{slug}',[CampaignRulesController::class,'getCampaignAdverts'])->middleware('throttle:60,1');

//


//COUPON

Route::post('coupon/apply',[CouponController::class,'activeCoupon'])->middleware([AuthMiddleware::class, 'throttle:5,1']);


// ADMIN REFUND 

/*
Route::post('admin/refund/{id}',[OrderController::class,'refund'])->middleware(AuthMiddleware::class);

Route::post('admin/refundCargo/{id}',[OrderController::class,'refundCargoDetails'])->middleware(AuthMiddleware::class);
*/
