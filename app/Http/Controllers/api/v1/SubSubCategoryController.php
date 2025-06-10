<?php

namespace App\Http\Controllers\api\v1;

use Carbon\Carbon;
use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class SubSubCategoryController extends ResponseController
{
    // get all subCategories
    public function getAllSubSubCategory()
    {
           // Fetch all subcategories with their associated categories
           $subSubCategories = DB::table('sub_sub_categories')
           ->join('sub_categories', 'sub_sub_categories.subCategoryId', '=', 'sub_categories.id')
           ->join('categories', 'sub_categories.categoryId', '=', 'categories.id')
           ->select(
               'sub_sub_categories.id as id', // Select ID from sub_sub_categories
               'sub_sub_categories.name as name', // Assuming there's a 'name' column
               'sub_sub_categories.cover as cover', // Assuming there's a 'cover' column
               'sub_categories.id as subCategoryId', // Select ID from sub_categories
               'sub_categories.name as subCategoryName', // Assuming there's a 'name' column
               'categories.id as categoryId', // Select ID from categories
               'categories.name as categoryName' // Assuming there's a 'name' column
           )
           ->whereNull('sub_sub_categories.deleted_at')
           ->get();
       $modifiedSubSubCategories = $subSubCategories->map(function ($subSubCategory) {
           return [
               'id' =>(string) $subSubCategory->id,
               'name' => $subSubCategory->name,
               'cover' => $subSubCategory->cover,
               'subCategoryId' => $subSubCategory->subCategoryId,
               'subCategoryName' => $subSubCategory->subCategoryName,
               'categoryId' => $subSubCategory->categoryId,
               'categoryName' => $subSubCategory->categoryName,
               'status' => $subSubCategory->status
           ];
       });

// Return the response in the desired format
return $this->sendResponse($modifiedSubSubCategories, 'All SubsubCategories');

    }


    // storeSubCategory
    public function storeSubSubCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'subCategoryId' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
        }

        $category = DB::table('sub_categories')->where('id', $request->subCategoryId)->first();
        if (!$category) {
            return $this->sendError('Sub Category not found', [], 404);
        }

        $findSubCategoryByName = DB::table('sub_sub_categories')->where('name', $request->name)->first();
        if ($findSubCategoryByName) {
            return $this->sendError('Sub Sub Category already exists', [], 409);
        }

        if ($request->file('cover')) {
            $subSubCategoryCover = $request->file('cover');
            $path = 'sub_categories/covers'; // Example path, adjust as needed
            $subSubCategoryCoverUrl = S3Service::uploadSingle($subSubCategoryCover, $path);
        }

        DB::table('sub_sub_categories')->insert([
            'name' => $request->name,
            'subCategoryId' => $request->subCategoryId,
            'cover' => $subSubCategoryCoverUrl ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->sendResponse('Sub Sub Category created successfully', 'Sub Sub Category created successfully.');
    }


    // updateSubCategory
    public function updateSubSubCategory(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'subCategoryId' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
        }

        $category = DB::table('sub_categories')->where('id', $request->subCategoryId)->first();
        if (!$category) {
            return $this->sendError('Sub Category not found', [], 404);
        }

        $subSubCategory = DB::table('sub_sub_categories')->where('id', $id)->first();
        if (!$subSubCategory) {
            return $this->sendError(' Sub Sub Category not found', [], 404);
        }

        if ($request->file('cover')) {
            $subSubCategoryCover = $request->file('cover');
            $path = 'sub_categories/covers'; // Example path, adjust as needed
            // Delete old cover
            S3Service::deleteFile($subSubCategory->cover);
            $subSubCategoryCoverUrl = S3Service::uploadSingle($subSubCategoryCover, $path);
        } else {
            $subSubCategoryCoverUrl = $subSubCategory->cover;
        }

        DB::table('sub_sub_categories')->where('id', $id)->update([
            'name' => $request->name ?? $subSubCategory->name,
            'subCategoryId' => $request->subCategoryId ?? $subSubCategory->subCategoryId,
            'cover' => $subSubCategoryCoverUrl ?? $subSubCategory->cover,
            'updated_at' => now(),
        ]);

        return $this->sendResponse('Sub Category updated successfully', 'Sub Category updated successfully.');
    }

    // updateSubCategoryStatus 
    public function updateSubSubCategoryStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Active,Inactive',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
        }

        $subCategory = DB::table('sub_sub_categories')->where('id', $id)->first();
        if (!$subCategory) {
            return $this->sendError('Sub Category not found', [], 404);
        }
       DB::table('sub_sub_categories')->where('id', $id)->update([
            'status' => $request->status,
            'updated_at' => now(),
       ]);

        return $this->sendResponse('Sub Category status updated successfully', 'Sub Category status updated successfully.');
    }


    // deleteSubCategory
    public function deleteSubSubCategory($id)
    {
        $subSubCategory = DB::table('sub_sub_categories')->where('id', $id)->first();
        if (!$subSubCategory) {
            return $this->sendError('Sub Category not found', [], 404);
        }

        // Delete old cover
        if($subSubCategory->cover && $subSubCategory->cover != null) {
            S3Service::deleteFile($subSubCategory->cover);
        }

        // update all products to deleted at
        DB::table('products')->where('id', $id)->update(['deleted_at' => Carbon::now()]);
        DB::table('sub_sub_categories')->where('id', $id)->update([''=> Carbon::now()]);
        return $this->sendResponse('Sub Category deleted successfully', 'Sub Category deleted successfully.');
    }
}
