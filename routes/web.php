<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SitemapController;



route::get('email',function (){
    return view('emails.order');
});

Route::get('/sitemap.xml', [SitemapController::class, 'index']);
