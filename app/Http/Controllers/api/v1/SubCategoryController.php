<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use Carbon\Carbon;
use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class SubCategoryController extends ResponseController
{

    // get all subCategories
    public function getAllSubCategory()
    {
           // Fetch all subcategories with their associated categories
    $subCategories = DB::table('sub_categories')
    ->join('categories', 'sub_categories.categoryId', '=', 'categories.id')
    ->select(
        'sub_categories.id',
        'sub_categories.name',
        'sub_categories.cover',
        'sub_categories.categoryId',
        'categories.name as categoryName',
        'sub_categories.status'
    )
    ->whereNull('sub_categories.deleted_at')
    ->get();

    $modifiedSubCategories = $subCategories->map(function ($subCategory) {
        return [
            'id' =>(string) $subCategory->id,
            'name' => $subCategory->name,
            // 'cover' => $subCategory->cover,
            'categoryId' => $subCategory->categoryId,
            'categoryName' => $subCategory->categoryName,
            'status' => $subCategory->status
        ];
    });

// Return the response in the desired format
return $this->sendResponse($modifiedSubCategories, 'All subCategories');

    }


    // storeSubCategory
    public function storeSubCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'categoryId' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
        }

        $category = DB::table('categories')->where('id', $request->categoryId)->first();
        if (!$category) {
            return $this->sendError('Category not found', [], 404);
        }

        $findSubCategoryByName = DB::table('sub_categories')->where('name', $request->name)->first();
        if ($findSubCategoryByName) {
            return $this->sendError('Sub Category already exists', [], 409);
        }
        DB::table('sub_categories')->insert([
            'name' => $request->name,
            'categoryId' => $request->categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->sendResponse('Sub Category created successfully', 'Sub Category created successfully.');
    }


    // updateSubCategory
    public function updateSubCategory(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'categoryId' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
        }

        $category = DB::table('categories')->where('id', $request->categoryId)->first();
        if (!$category) {
            return $this->sendError('Category not found', [], 404);
        }

        $subCategory = DB::table('sub_categories')->where('id', $id)->first();
        if (!$subCategory) {
            return $this->sendError('Sub Category not found', [], 404);
        }


        DB::table('sub_categories')->where('id', $id)->update([
            'name' => $request->name ?? $subCategory->name,
            'categoryId' => $request->categoryId ?? $subCategory->categoryId,
            'updated_at' => now(),
        ]);

        return $this->sendResponse('Sub Category updated successfully', 'Sub Category updated successfully.');
    }

    // updateSubCategoryStatus 
    public function updateSubCategoryStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Active,Inactive',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
        }

        $subCategory = DB::table('sub_categories')->where('id', $id)->first();
        if (!$subCategory) {
            return $this->sendError('Sub Category not found', [], 404);
        }
       DB::table('sub_categories')->where('id', $id)->update([
            'status' => $request->status,
            'updated_at' => now(),
       ]);

        return $this->sendResponse('Sub Category status updated successfully', 'Sub Category status updated successfully.');
    }


    // deleteSubCategory
    public function deleteSubCategory($id)
    {
        $subCategory = DB::table('sub_categories')->where('id', $id)->first();
        if (!$subCategory) {
            return $this->sendError('Sub Category not found', [], 404);
        }


            // Delete all sub-subcategories associated with this subcategory
            DB::table('sub_sub_categories')->where('subCategoryId', $id)->delete();
            
            DB::table('products')
            ->where('subCategoryId', $id)  // Products related to this subcategory
          // Products related to sub-subcategories (if applicable)
            ->update(['deleted_at' => Carbon::now()]); 


        DB::table('sub_categories')->where('id', $id)->update([
            'deleted_at' => Carbon::now(),
        ]);


        return $this->sendResponse('Sub Category deleted successfully', 'Sub Category deleted successfully.');
    }

    // get all sub categories by category id 
    public function getSubCategoriesByCategoryId($categoryId)
    {
        try{
            $subCategories = DB::table('sub_categories')
            ->leftJoin('categories', 'sub_categories.categoryId', '=', 'categories.id')
            ->where('categoryId', $categoryId)
            ->select('sub_categories.id as id','sub_categories.name', 'categories.name as categoryName', 'categories.status','categories.id as categoryId')
            ->get();



        return $this->sendResponse($subCategories, 'Sub Categories retrieved successfully.');
        }catch(Exception $e){
            return $this->sendError('Error', $e->getMessage(), 500);
        }
    }
}
