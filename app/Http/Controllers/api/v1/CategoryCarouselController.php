<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CategoryCarouselController extends ResponseController
{
    //storeCategoryCarousel

    public function storeCategoryCarousel(Request $request){

       try{
        $validator = Validator::make($request->all(), [
            'isVideo' => 'required',
            'src' => 'required',
            'categoryId' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $category = DB::table('categories')->where('id', $request->categoryId)->first();
        if (!$category) {
            return $this->sendError('Category not found', [], 404);
        }

        // // store src file in  s3 
        $file = $request->file('src');
        $path = 'category-carousel'; // Example path, adjust as needed
        $categoryCarouselUrl = S3Service::uploadSingle($file, $path);



        DB::table('categories_carousel')->insert([
            'isVideo' => $request->isVideo,
            'src' => $categoryCarouselUrl,
            'categoryId' => $request->categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $this->sendResponse('Category Carousel created successfully', 'Category Carousel created successfully');
       }catch(Exception $e){

        return $this->sendError('Something went wrong', [], 500);
       }

    }

    // updateCategoryCarousel 
    public function updateCategoryCarousel(Request $request, $id){

       try{
        $findCategoriesCarousel = DB::table('categories_carousel')->where('id', $id)->first();
        if (!$findCategoriesCarousel) {
            return $this->sendError('Category Carousel not found', [], 404);
        }
        DB::table('categories_carousel')->where('id', $id)->update([
            'status' => $request->status
        ]);
        return $this->sendResponse('Category Carousel updated successfully', 'Category Carousel updated successfully');
       }catch(Exception $e){
        return $this->sendError('Something went wrong', [], 500);
       }
    }


    // deleteCategoryCarousel 
    public function deleteCategoryCarousel($id){
       try{
        $findCategoriesCarousel = DB::table('categories_carousel')->where('id', $id)->first();
        if (!$findCategoriesCarousel) {
            return $this->sendError('Category Carousel not found', [], 404);
        }
        // delete src file from s3
       if($findCategoriesCarousel->src){
        S3Service::deleteFile($findCategoriesCarousel->src);
       }
        DB::table('categories_carousel')->where('id', $id)->delete();
        return $this->sendResponse('Category Carousel deleted successfully', 'Category Carousel deleted successfully');
       }catch(Exception $e){
        return $this->sendError('Something went wrong', [], 500);
       }
    }


    // getAllCategoryCarousel 
    public function getAllCategoryCarousel(){
        try{
        $categoryCarousel = DB::table('categories_carousel')
        ->leftJoin('categories', 'categories_carousel.categoryId', '=', 'categories.id')
        ->select(
            'categories_carousel.*',
            'categories.name as categoryName',
            'categories.id as categoryId'
        )
        ->get();

        $modifiedCategoryCarousel = $categoryCarousel->map(function ($category) {
            return [
                'id' => (string) $category->id,
                'isVideo' => $category->isVideo,
                'src' => $category->src,
                'categoryId' => (string) $category->categoryId,
                'categoryName' => $category->categoryName,
                'status' => $category->status,
                'date' => $category->created_at
            ];
        });

        return $this->sendResponse($modifiedCategoryCarousel, 'Category Carousel fetched successfully');
       }catch(Exception $e){
        return $this->sendError('Something went wrong', [], 500);
       }
    }
}
