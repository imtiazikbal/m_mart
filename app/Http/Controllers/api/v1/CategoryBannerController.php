<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CategoryBannerController extends ResponseController
{
    // store and update top category
   public function store(Request $request)
{
    try {
        // Validate that the category exists
        $category = DB::table('categories')->where('id', $request->categoryId)->first();
        if (!$category) {
            return $this->sendError('Category not found', [], 404);
        }

        // Upload the new image to S3
        $file = $request->file('image');
        $path = 'category_banner';
        $categoryCarouselUrl = S3Service::uploadSingle($file, $path);

        // Check if a category banner already exists
        $categoryBanner = DB::table('category_banner')->first();

        if ($categoryBanner) {
            // Delete the old image from S3 if it exists
            if ($categoryBanner->image) {
                S3Service::deleteFile($categoryBanner->image);
            }

            // Update the existing category banner
            DB::table('category_banner')->where('id', $categoryBanner->id)->update([
                'subCategoryId' => $request->subCategoryId,
                'image' => $categoryCarouselUrl,
                'categoryId' => $request->categoryId,
                'updated_at' => now(),
            ]);
        } else {
            // Insert a new category banner
            DB::table('category_banner')->insert([
                'subCategoryId' => $request->subCategoryId,
                'image' => $categoryCarouselUrl,
                'categoryId' => $request->categoryId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->sendResponse('Category Banner created successfully', 'Category Carousel created successfully');
    } catch (Exception $e) {
        // Optionally log the error: Log::error($e->getMessage());
        return $this->sendError('Something went wrong', [], 500);
    }
}


    

    // get all top categories
    public function getAll(){
        try{
            $topCategories = DB::table('category_banner')->
            leftJoin('categories', 'category_banner.categoryId', '=', 'categories.id')
            ->leftJoin('sub_categories', 'category_banner.subCategoryId', '=', 'sub_categories.id')->
            select(
                'category_banner.id as id',
                'category_banner.image as image',
                'categories.name as categoryName',
                'categories.id as categoryId',
                'sub_categories.id as subCategoryId',
                'sub_categories.name as subCategoryName',
            )->
            first();
            return $this->sendResponse($topCategories, 'Top categories fetched successfully');
           }catch(Exception $e){
            return $this->sendError('Something went wrong', [], 500);
           }
    }

    // getAllForFrontend
   public function getAllForFrontend(){
    try {
        $topCategories = DB::table('category_banner')
            ->leftJoin('categories', 'category_banner.categoryId', '=', 'categories.id')
            ->leftJoin('sub_categories', 'category_banner.subCategoryId', '=', 'sub_categories.id')
            ->leftJoin('products', 'sub_categories.id', '=', 'products.subCategoryId')
            ->select(
                'category_banner.image as imageUrl',
                'categories.name as categoryName',
                'sub_categories.name as subCategoryName',
                DB::raw('MIN(products.price) as startingPrice')
            )
            ->groupBy(
                'category_banner.image',
                'categories.name',
                'sub_categories.name'
            )
            ->first();

        return $this->sendResponse($topCategories, 'Top categories fetched successfully');
    } catch(Exception $e) {
        return $this->sendError('Something went wrong', [], 500);
    }
}

}
