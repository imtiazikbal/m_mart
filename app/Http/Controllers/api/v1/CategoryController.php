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
                'icon' => $category->icon,
                'cover' => $category->cover,
                'thumbnail' => $category->thumbnail,
                'showInProductBar' => $category->showInProductBar,
                'showInIconBar' => $category->showInIconBar,
                'showInHeaderBar' => $category->showInHeaderBar,
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
        $findCategoryByName = DB::table('categories')->where('name', $request->name)->first();
        if ($findCategoryByName) {
            return $this->sendError('Category already exists', [], 409);
        }

        
        if($request->file('cover')){
            $categoryCover = $request->file('cover');
             // Define the path where you want to store the file
         $path = 'categories/covers'; // Example path, adjust as needed
 
         // Use the UploadService to upload the file to S3
         $categoryCoverUrl= S3Service::uploadSingle($categoryCover, $path);

        }

        if($request->file('icon')){
            $categoryIcon = $request->file('icon');
            $path = 'categories/icons'; // Example path, adjust as needed
 
            // Use the UploadService to upload the file to S3
            $categoryIconUrl= S3Service::uploadSingle($categoryIcon, $path);
        }

        if($request->file('thumbnail')){
            $thumbnailIcon = $request->file('thumbnail');
            $path = 'categories/thumbnail'; // Example path, adjust as needed
 
            // Use the UploadService to upload the file to S3
            $thumbnailIconUrl= S3Service::uploadSingle($thumbnailIcon, $path);
        }



     
        
        $categoryCover = $request->file('cover');
        $category = DB::table('categories')->insert([
            'name' => $request->name,
            'showInHeaderBar' => $request->showInHeaderBar,
            'showInIconBar' => $request->showInIconBar,
            'showInProductBar' => $request->showInProductBar,
            'icon' => $categoryIconUrl ?? null,
            'cover' => $categoryCoverUrl ?? null,
            'thumbnail' => $thumbnailIconUrl ?? null,
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

    // Update cover if a new file is uploaded
    if ($request->file('cover')) {
        $categoryCover = $request->file('cover');
        $path = 'categories/covers';

        // Delete the old cover file from S3
        if ($category->cover) {
            S3Service::deleteFile($category->cover);
        }

        // Upload the cover file to S3
        $categoryCoverUrl = S3Service::uploadSingle($categoryCover, $path);
    } else {
        // Keep the current cover URL if no new file is provided
        $categoryCoverUrl = $category->cover;
    }

    // Update icon if a new file is uploaded
    if ($request->file('icon')) {
        $categoryIcon = $request->file('icon');
        $path = 'categories/icons';
// Delete the old cover file from S3
if ($category->icon) {
    S3Service::deleteFile($category->icon);
}
        // Upload the icon file to S3
        $categoryIconUrl = S3Service::uploadSingle($categoryIcon, $path);
    } else {
        // Keep the current icon URL if no new file is provided
        $categoryIconUrl = $category->icon;
    }

    // Update thumbnail if a new file is uploaded
    if ($request->file('thumbnail')) {
        $thumbnailIcon = $request->file('thumbnail');
        // Delete the old cover file from S3
if ($category->icon) {
    S3Service::deleteFile($category->icon);
}
        $path = 'categories/thumbnail';
        // Upload the thumbnail file to S3
        $thumbnailIconUrl = S3Service::uploadSingle($thumbnailIcon, $path);
    } else {
        // Keep the current thumbnail URL if no new file is provided
        $thumbnailIconUrl = $category->thumbnail;
    }





    // Update category in the database
    DB::table('categories')->where('id', $id)->update([
        'name' => $request->name ?? $category->name,
        'showInHeaderBar' => $request->showInHeaderBar ?? $category->showInHeaderBar,
        'showInIconBar' => $request->showInIconBar ?? $category->showInIconBar,
        'showInProductBar' => $request->showInProductBar ?? $category->showInProductBar,
        'icon' => $categoryIconUrl,
        'cover' => $categoryCoverUrl,
        'thumbnail' => $thumbnailIconUrl,
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

     // 2. Delete associated files from S3
     $filesToDelete = [$category->icon, $category->cover, $category->thumbnail];
     foreach ($filesToDelete as $file) {
         if (isset($file) && $file !== null) {
             S3Service::deleteFile($file);
         }
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

     // 7. Soft delete the sub-subcategories associated with the subcategories (if needed)
     foreach ($subCategories as $subCategory) {
         DB::table('sub_sub_categories')
             ->where('subCategoryId', $subCategory->id)
             ->update(['deleted_at' => Carbon::now()]); // Soft delete
     }

     // 8. Finally, delete the category
     DB::table('categories')->where('id', $id)->update(['deleted_at' => Carbon::now()]); // Soft delete

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
             ->leftJoin('sub_categories', function ($join) {
                 $join->on('sub_categories.categoryId', '=', 'categories.id')
                      ->where('sub_categories.status', 'Active')  // Only active subcategories
                      ->whereNull('sub_categories.deleted_at');
             })
             ->leftJoin('sub_sub_categories', function ($join) {
                 $join->on('sub_sub_categories.subCategoryId', '=', 'sub_categories.id')
                      ->where('sub_sub_categories.status', 'Active')  // Only active sub-sub-categories\
                      ->whereNull('sub_sub_categories.deleted_at');
             })
             ->select(
                 DB::raw('CAST(categories.id AS CHAR) as category_id'),
                 'categories.name as category_name',
                 DB::raw('CAST(sub_categories.id AS CHAR) as sub_category_id'),
                 'sub_categories.name as sub_category_name',
                 DB::raw('CAST(sub_sub_categories.id AS CHAR) as sub_sub_category_id'),
                 'sub_sub_categories.name as sub_sub_category_name'
             )
             ->get();
     
         // Transform the data into the required nested structure
         $responseData = [];
         foreach ($categories as $category) {
             // Find or create the category in the response data
             $categoryIndex = array_search($category->category_id, array_column($responseData, 'id'));
             if ($categoryIndex === false) {
                 $responseData[] = [
                     'id' => (string) $category->category_id,
                     'name' => (string) $category->category_name,
                     'subCategory' => []
                 ];
                 $categoryIndex = count($responseData) - 1;
             }
     
             // Check if there is an active subcategory
             if ($category->sub_category_id) {
                 $subCategories = &$responseData[$categoryIndex]['subCategory'];
     
                 // Find or create the sub-category in the category's subCategory array
                 $subCategoryIndex = array_search($category->sub_category_id, array_column($subCategories, 'id'));
                 if ($subCategoryIndex === false) {
                     $subCategories[] = [
                         'id' => (string) $category->sub_category_id,
                         'title' => (string) $category->sub_category_name,
                         'subSubCategory' => []
                     ];
                     $subCategoryIndex = count($subCategories) - 1;
                 }
     
                 // Add the sub-sub-category to the sub-category's subSubCategory array only if it exists
                 if ($category->sub_sub_category_id) {
                     $subCategories[$subCategoryIndex]['subSubCategory'][] = [
                         'id' => (string) $category->sub_sub_category_id,
                         'title' => (string) $category->sub_sub_category_name
                     ];
                 }
             }
         }
     
         // Return the data in the required response structure
         return $this->sendResponse($responseData,'Header categories retrieved successfully');
     }
}
