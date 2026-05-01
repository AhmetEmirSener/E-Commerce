<?php

use Illuminate\Support\Facades\Route;



route::get('email',function (){
    return view('emails.order');
});