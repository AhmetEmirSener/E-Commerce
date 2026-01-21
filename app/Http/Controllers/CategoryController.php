<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

use App\Http\Requests\CategoryStoreRequest;

use App\Services\FileUploadService;
use App\Services\SlugCreateService;
use App\Http\Resources\CategoryResource;



class CategoryController extends Controller
{
    protected FileUploadService $fileUploadService;
    protected SlugCreateService $slugService;

    public function __construct(FileUploadService $fileUploadService, SlugCreateService $slugService){
        $this->fileUploadService=$fileUploadService;

        $this->slugService=$slugService;
    }
    

    public function storeCategory(CategoryStoreRequest $request){
        try {
            $data = $request->validated();

            $data['slug'] = $this->slugService->createSlug($data,\App\Models\Category::class);
            unset($data['image']);
            $category = Category::create($data);

            $this->fileUploadService->storeFile($category,$request->file('image'),'categories');

            return response()->json(['message' => 'Kategori oluşturuldu..'], 200);

        } catch (\Throwable $e) {
            return response()->json(['error'=>$e->getMessage()],500);

        }
    }

}
