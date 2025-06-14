<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class BottomCategoryController extends ResponseController
{
     // store top category
    public function store(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
            'image' => 'required',
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
        $file = $request->file('image');
        $path = 'bottom_categories'; // Example path, adjust as needed
        $categoryCarouselUrl = S3Service::uploadSingle($file, $path);



        DB::table('bottom_categories')->insert([
            'subCategoryId' => $request->subCategoryId,
            'image' => $categoryCarouselUrl,
            'categoryId' => $request->categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $this->sendResponse('Category Carousel created successfully', 'Category Carousel created successfully');
        }catch(Exception $e){
            return $this->sendError('Something went wrong', [], 500);
        }
    }

    // update bottom_categories
    public function update(Request $request, $id){
        try{
            $findCategoriesCarousel = DB::table('bottom_categories')->where('id', $id)->first();
            if (!$findCategoriesCarousel) {
                return $this->sendError('Category Carousel not found', [], 404);
            }
            // delete src file from s3
           if($findCategoriesCarousel->image){
            S3Service::deleteFile($findCategoriesCarousel->image);
           }
           if($request->image){
            $file = $request->file('image');
            $path = 'bottom_categories'; // Example path, adjust as needed
            $categoryCarouselUrl = S3Service::uploadSingle($file, $path);
            DB::table('bottom_categories')->where('id', $id)->update([
                'image' => $categoryCarouselUrl ?? $findCategoriesCarousel->image,
                'categoryId' => $request->categoryId ?? $findCategoriesCarousel->categoryId,
                'subCategoryId' => $request->subCategoryId ?? $findCategoriesCarousel->subCategoryId,
                'updated_at' => now(),

            ]);
           }
          
            return $this->sendResponse('Category Carousel updated successfully', 'Category Carousel updated successfully');
           }catch(Exception $e){
            return $this->sendError('Something went wrong', [], 500);
           }
    }

    // delete top category by id
    public function delete($id){
        try{
            $findCategoriesCarousel = DB::table('bottom_categories')->where('id', $id)->first();
            if (!$findCategoriesCarousel) {
                return $this->sendError('Category Carousel not found', [], 404);
            }
            // delete src file from s3
           if($findCategoriesCarousel->image){
            S3Service::deleteFile($findCategoriesCarousel->image);
           }
            DB::table('bottom_categories')->where('id', $id)->delete();
            return $this->sendResponse('Category Carousel deleted successfully', 'Category Carousel deleted successfully');
           }catch(Exception $e){
            return $this->sendError('Something went wrong', [], 500);
           }
    }

    // get all top categories
    public function getAll(){
        try{
            $topCategories = DB::table('bottom_categories')->
            leftJoin('categories', 'bottom_categories.categoryId', '=', 'categories.id')
            ->leftJoin('sub_categories', 'bottom_categories.subCategoryId', '=', 'sub_categories.id')->
            select(
                'bottom_categories.id as id',
                'bottom_categories.image as image',
                'categories.name as categoryName',
                'categories.id as categoryId',
                'sub_categories.id as subCategoryId',
                'sub_categories.name as subCategoryName',
            )->
            get();
            return $this->sendResponse($topCategories, 'Top categories fetched successfully');
           }catch(Exception $e){
            return $this->sendError('Something went wrong', [], 500);
           }
    }
    // getAllForFrontend
    public function getAllForFrontend(){
        try{
            $topCategories = DB::table('bottom_categories')->
            leftJoin('categories', 'bottom_categories.categoryId', '=', 'categories.id')
            ->leftJoin('sub_categories', 'bottom_categories.subCategoryId', '=', 'sub_categories.id')->
            select(
                'bottom_categories.image as imageUrl',
                'categories.name as categoryName',
                'sub_categories.name as subCategoryName',
            )->
            get();
            return $this->sendResponse($topCategories, 'Top categories fetched successfully');
           }catch(Exception $e){
            return $this->sendError('Something went wrong', [], 500);
           }
    }
}
