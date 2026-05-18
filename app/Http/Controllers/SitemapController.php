<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Advert;
use App\Models\Category;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(){
        $adverts = Advert::select('slug','updated_at')->get();
        $categories = Category::select('slug','updated_at')->get();

        $content = view('sitemap',compact('adverts','categories'))->render();

        return response($content,200)->header('Content-type','application/xml');
    }
}
