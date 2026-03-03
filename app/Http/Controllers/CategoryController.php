<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Advert;
use App\Models\Product;

use Illuminate\Http\Request;

use App\Http\Requests\CategoryStoreRequest;

use App\Services\FileUploadService;
use App\Services\SlugCreateService;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\miniAdvertResource;

use App\Services\CategoryService;


class CategoryController extends Controller
{
    protected FileUploadService $fileUploadService;
    protected SlugCreateService $slugService;
    protected CategoryService $categoryService;

    public function __construct(FileUploadService $fileUploadService,
     SlugCreateService $slugService,
     CategoryService $categoryService
     ){
        $this->fileUploadService=$fileUploadService;
        $this->slugService=$slugService;
        $this->categoryService=$categoryService;
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

    public function getCategories(){
        try {
            $categories = Category::whereNull('parent_id')
            ->with('getChild')
            ->get();
            $categories->each(function ($category) {
                $category->popular_adverts = $category->popularAdvertsWithChildren()->get();
            });
            //return response()->json($categories);
            return CategoryResource::collection($categories);
                                
        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()],500);
        }
    }

    public function getCategoryTree($slug){
        try {
            $category = Category::where('slug',$slug)->first();
            if(!$category)return response()->json(['message'=>'Kategori bulunamadı'],404);
            $tree = $this->categoryService->branchWithParentsAndChildren($category);
            $path = $this->categoryService->breadcrumb($category);
        
            return response()->json([
                'filters'=>[
                    'breadcrumb'=>$path,
                    'category_tree'=> new CategoryResource($tree),
                    'active_category'=>$category
                ],
            ]);
        
        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()]);
        }
    }

    public function searchByCategory(Request $request){
        try {
            $category = Category::where('slug',$request->slug)->first();
            if(!$category)return response()->json(['message'=>'Kategori bulunamadı'],404);
            
            $childIds= $category->getAllChildrenIds();
            $categoryIds = collect($childIds)->push($category->id);


            $allowedAdvert = ['avg_rating'];
            $allowedProduct = ['price'];


            $adverts = Advert::query()
            ->with('product.activeDiscount')
            ->whereHas('product',function ($q) use ($categoryIds){
                $q->whereIn('category_id',$categoryIds);
            })
            ->when(
                in_array($request->sort_by, $allowedAdvert),
                fn ($q) => $q->orderBy($request->sort_by, $request->order ?? 'desc')
            )
            ->when(
                in_array($request->sort_by,$allowedProduct),
                function ($q) use ($request){
                    $q->orderBy(
                        Product::select($request->sort_by)
                        ->whereColumn('products.id','adverts.product_id'),
                        $request->order ?? 'desc'
                    );
                }
            )->when($request->filled('min_price') || $request->filled('max_price'),
                function($q) use ($request){

                    $q->whereHas('product', function ($q2) use ($request){
                        if($request->filled('min_price') && $request->filled('max_price')){
                            $q2->whereBetween('price',[
                                $request->min_price,
                                $request->max_price
                            ]);             
                        }
                        elseif($request->filled('min_price')){
                            $q2->where('price','>=',$request->min_price);
                        }elseif($request->filled('max_price')){
                            $q2->where('price','<=',$request->max_price);
                        }
                    });
                }
            )
            ->paginate(12);


            return response()->json([
                'data'=>miniAdvertResource::collection($adverts),
                'meta' => [
                        'previous_page'=>$adverts->previousPageUrl(),
                        'current_page' => $adverts->currentPage(),
                        'last_page' => $adverts->lastPage(),

                        'total' => $adverts->total(),]

            ]);
        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()]);
        }
    }

}
