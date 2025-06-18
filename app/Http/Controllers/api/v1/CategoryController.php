<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use Carbon\Carbon;
use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CategoryController extends ResponseController
{


    // getAllCategory 
    public function getAllCategory(){
        try{
            $categories = DB::table('categories')->
           whereNull('deleted_at')->get();
        if (!$categories) {
            return $this->sendError('Category not found', [], 404);
        }

        $modifiedCategories = $categories->map(function ($category) {
            return [
                'id' => (string) $category->id,
                'name' => $category->name,
                // 'icon' => $category->icon,
                // 'cover' => $category->cover,
                // 'thumbnail' => $category->thumbnail,
                // 'showInProductBar' => $category->showInProductBar,
                // 'showInIconBar' => $category->showInIconBar,
                // 'showInHeaderBar' => $category->showInHeaderBar,
                'status' => $category->status

            ];
        });
        return $this->sendResponse($modifiedCategories, 'Categories retrieved successfully.');
        }catch(Exception $e){
            return $this->sendError('', $e->getMessage());
        }
    }
    // storeCategory
    public function storeCategory(Request $request){
       
        $validator = Validator::make($request->all(), [
            'name' => 'required'
            
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
        }
        $findCategoryByName = DB::table('categories')->whereRaw('LOWER(name) = ?', [strtolower($request->name)])->whereNull('deleted_at')->where('status', 'Active')->first();
        if ($findCategoryByName) {
            return $this->sendError('Category already exists', [], 409);
        }

        
       
     DB::table('categories')->insert([
            'name' => $request->name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $this->sendResponse('Category created successfully', 'Category created successfully.');
        
        
    }

    // Update Category
public function updateCategory(Request $request) {
    
    // Validation
    $validator = Validator::make($request->all(), [
        'name' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
    }

    $id = $request->id;

    // Find category by ID
    $category = DB::table('categories')->where('id', $id)->first();
    if (!$category) {
        return $this->sendError('Category not found', [], 404);
    }

    // Check if name is being updated and already exists
    if ($request->has('name') && $request->name !== $category->name) {
        $findCategoryByName = DB::table('categories')->where('name', $request->name)->first();
        if ($findCategoryByName) {
            return $this->sendError('Category with this name already exists', [], 409);
        }
    }

    // Update category in the database
    DB::table('categories')->where('id', $id)->update([
        'name' => $request->name ?? $category->name,
        'updated_at' => now(),
    ]);

    return $this->sendResponse(
        'Category updated successfully', 'Category updated successfully.');
}

// updateCategoryStatus 
public function updateCategoryStatus(Request $request, $id) {

    $validator = Validator::make($request->all(), [
        'status' => 'required|in:Active,Inactive',
    ]);

    if ($validator->fails()) {
        return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
    }

    $category = DB::table('categories')->where('id', $id)->first();
    if (!$category) {
        return $this->sendError('Category not found', [], 404);
    }

    DB::table('categories')->where('id', $id)->update([
        'status' => $request->status,
        'updated_at' => now(),
    ]);

    return $this->sendResponse(
        'Category status updated successfully', 'Category status updated successfully.');

}

// deleteCategory
public function deleteCategory(Request $request, $id) {
     // 1. Find the category to be deleted
     $category = DB::table('categories')->where('id', $id)->where('deleted_at', null)->first();
     if (!$category) {
         return $this->sendError('Category not found', [], 404);
     }

   

     // 3. Find all subcategories associated with this category
     $subCategories = DB::table('sub_categories')->where('categoryId', $id)->get();

     // 4. Soft delete all sub-subcategories and associated products for each subcategory
     foreach ($subCategories as $subCategory) {
         // Soft delete sub-subcategories
         $subSubCategories = DB::table('sub_sub_categories')
             ->where('subCategoryId', $subCategory->id)
             ->get();

         // Soft delete products associated with each sub-subcategory
         foreach ($subSubCategories as $subSubCategory) {
             DB::table('products')
                 ->where('subSubCategoryId', $subSubCategory->id)
                 ->update(['deleted_at' => Carbon::now()]); // Soft delete
         }

         // Soft delete products directly associated with each subcategory
         DB::table('products')
             ->where('subCategoryId', $subCategory->id)
             ->update(['deleted_at' => Carbon::now()]); // Soft delete
     }

     //5. Soft delete all products associated with the category
     DB::table('products')
         ->where('categoryId', $id)
         ->update(['deleted_at' => Carbon::now()]); // Soft delete

     // 6. Soft delete the subcategories associated with the category (if needed)
     DB::table('sub_categories')
         ->where('categoryId', $id)
         ->update(['deleted_at' => Carbon::now()]); // Soft delete

     // 8. Finally, delete the category
     DB::table('categories')->where('id', $id)->update(['deleted_at' => Carbon::now(),'status' => 'Inactive']); // Soft delete

    return $this->sendResponse(
        'Category deleted successfully', 'Category deleted successfully.');
    }










    //  // get all categories for admin

     public function getAllCategoryForAdmin()
     {
         // Fetch only active categories with showInHeaderBar = 1 and their associated active subcategories and sub-subcategories
         $categories = DB::table('categories')
             ->where('categories.status', 'Active') // Only active categories
             ->whereNull('categories.deleted_at') // Exclude deleted categories


             ->select(
                 'categories.id',
                 'categories.name',
                 'categories.satatus'
             )
             ->get();
     
         // Transform the data into the required nested structure
         $responseData = $categories->map(function ($category) {
             return [
                 'id' => $category->id,
                 'name' => $category->name,
                 'status' => $category->status,
             ];
         });
         
         // Return the data in the required response structure
         return $this->sendResponse($responseData,'Header categories retrieved successfully');
     }

     // getUniqueCategory status active 
     public function getUniqueCategoryStatusActive() {
    try {
        // Fetch unique category names where status is 'Active' and not soft-deleted
        $categoryNames = DB::table('categories')
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('name');

        if ($categoryNames->isEmpty()) {
            return $this->sendError('No active categories found', [], 404);
        }

        return $this->sendResponse($categoryNames, 'Active categories retrieved successfully.');
        
    } catch (Exception $e) {
        return $this->sendError('Error retrieving categories', $e->getMessage(), 500);
    }
}

}
