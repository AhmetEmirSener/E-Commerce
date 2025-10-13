<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/getProducts',[ProductController::class,'getProducts']);

Route::post('/storeProduct',[ProductController::class,'createProduct']);

Route::put('/updateProduct/{id}',[ProductController::class,'updateProduct']);

Route::delete('/deleteProduct/{id}',[ProductController::class,'deleteProduct']);