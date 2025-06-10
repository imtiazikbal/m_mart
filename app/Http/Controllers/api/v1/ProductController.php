<?php

namespace App\Http\Controllers\api\v1;

use App\Services\S3Service;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends ResponseController
{

// get single product by id
    public function getSingleProduct($productId)
    {
        // Fetch the product with its associated categories, sub-categories, and sub-sub-categories
        $singleProduct = DB::table('products')
            ->leftJoin('categories', 'products.categoryId', '=', 'categories.id')
            ->leftJoin('sub_categories', 'products.subCategoryId', '=', 'sub_categories.id')
            ->leftJoin('sub_sub_categories', 'products.subSubCategoryId', '=', 'sub_sub_categories.id')
            ->where('products.id', $productId)
            ->whereNull('products.deleted_at')
            ->select(
                'products.id as id',
                'products.title as title',
                DB::raw('CAST(products.price AS DECIMAL(10,2)) as price'),
                DB::raw('CAST(products.discountPercent AS DECIMAL(10,2)) as discountPercent'),
                'products.displayImageSrc as displayImageSrc',
                'products.hoverImageSrc as hoverImageSrc',
                'products.categoryId as categoryId',
                'products.subCategoryId as subCategoryId',
                'products.subSubCategoryId as subSubCategoryId',
                DB::raw('CAST(products.productQuantity AS UNSIGNED) as productQuantity'),
                'products.material as material',
                'products.size as size',
                'products.capacity as capacity',
                'products.isFeatured as isFeatured',
                'products.isBestSelling as isBestSelling',
                'products.isFestiveDelights as isFestiveDelights',
                'products.isRecommended as isRecommended',
                'products.description as description',
                'products.status as status',

                // Include category, sub-category, and sub-sub-category names (or null if not found)
                'categories.id as categoryId',
                'categories.name as categoryName',
                'sub_categories.id as subCategoryId',
                'sub_categories.name as subCategoryName',
                'sub_sub_categories.id as subSubCategoryId',
                'sub_sub_categories.name as subSubCategoryName'
            )
            ->first();

        // Check if the product was found
        if (!$singleProduct) {
            return $this->sendError('Product not found', [], 404);
        }

        // Fetch gallery images for the product
        $galleryImages = DB::table('product_galleries')
            ->where('productId', $productId)
            ->pluck('galleryImageSrc');

        // Fetch reviews for the product
// Fetch the list of reviews with customer details
        $reviewList = DB::table('product_reviews')
            ->join('users', 'product_reviews.userId', '=', 'users.id')
            ->where('product_reviews.productId', $productId)
            ->select(
                'product_reviews.id',
                'product_reviews.rating',
                'product_reviews.review',
                DB::raw("CONCAT(users.firstName, ' ', users.lastName) as customerName"),
                'product_reviews.created_at as date'
            )
            ->get();

// Fetch the average rating for the product
        $averageRating = DB::table('product_reviews')
            ->where('product_reviews.productId', $productId)
            ->avg('rating');

// Round to one decimal place if needed
        $averageRating = round($averageRating, 1);

        // Fetch FAQs for the product
        $faqList = DB::table('ask_questions')
            ->where('productId', $productId)
            ->select('id', 'question', 'answer')
            ->get();

        // Prepare the final response structure
        $response = [
            'id' => (string) $singleProduct->id,
            'title' => (string) $singleProduct->title,
            'price' => (float) $singleProduct->price,
            'discountPercent' => (float) $singleProduct->discountPercent,
            'displayImageSrc' => (string) $singleProduct->displayImageSrc,
            'hoverImageSrc' => (string) $singleProduct->hoverImageSrc,
            'categoryId' => (string) $singleProduct->categoryId,
            'categoryName' => (string) $singleProduct->categoryName,
            'subCategoryId' => (string) $singleProduct->subCategoryId,
            'subCategoryName' => (string) $singleProduct->subCategoryName,
            'subSubCategoryId' => (string) $singleProduct->subSubCategoryId,
            'subSubCategoryName' => (string) $singleProduct->subSubCategoryName,
            'productQuantity' => (int) $singleProduct->productQuantity,
            'material' => (string) $singleProduct->material,
            'size' => (string) $singleProduct->size,
            'capacity' => (string) $singleProduct->capacity,
            'isFeatured' => (string) $singleProduct->isFeatured,
            'isBestSelling' => (string) $singleProduct->isBestSelling,
            'isFestiveDelights' => (string) $singleProduct->isFestiveDelights,
            'isRecommended' => (string) $singleProduct->isRecommended,
            'description' => (string) $singleProduct->description,
            'galleryImages' => $galleryImages->toArray(), // Convert collection to array
            'reviewList' => $reviewList ?? [],
            'faqList' => $faqList,
            'star' => $averageRating ?? 0,
        ];

        // Return the response
        return $this->sendResponse($response, 'Single Product retrive');
    }

// getAllCategoryForAdmin cat sub sub cat

// getAllProductForAdmin
    public function getAllProductForAdmin()
    {

        // Fetch all products
        $allProducts = DB::table('products')->where('deleted_at', null)->get();

        // Count total product reviews
        $countTotalProductReview = DB::table('product_reviews')->count();

        // Modify the product data
        $modifiedData = $allProducts->map(function ($item) use ($countTotalProductReview) {
            return [
                'id' => (string) $item->id,
                'title' => (string) $item->title,
                'price' => (float) $item->price,
                'discountPercent' => (float) $item->discountPercent,
                'productQuantity' => (int) $item->productQuantity,
                'displayImageSrc' => (string) $item->displayImageSrc,
                'status' => (string) $item->status, // Assuming status is a column in products
                'totalReview' => $countTotalProductReview,
            ];
        });
        return $this->sendResponse($modifiedData, 'Product retrieved successfully.');
    }

    //storeProduct
    public function storeProduct(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'categoryId' => 'required',
            'price' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors());
        }

        $findCategory = DB::table('categories')->where('id', $request->categoryId)->first();
        if (!$findCategory) {
            return $this->sendError('Category not found', [], 404);
        }
        if ($request->subCategoryId) {
            $findSubCategory = DB::table('sub_categories')->where('id', $request->subCategoryId)->first();
            if (!$findSubCategory) {
                return $this->sendError('Sub Category not found', [], 404);
            }
        }

        if ($request->subSubCategoryId) {
            $findSubSubCategory = DB::table('sub_sub_categories')->where('id', $request->subSubCategoryId)->first();
            if (!$findSubSubCategory) {
                return $this->sendError('Sub Sub Category not found', [], 404);
            }
        }

        $displayImageSrcFile = $request->file('displayImageSrc');
        $path = 'products/display_images'; // Example path, adjust as needed
        $displayImageSrc = S3Service::uploadSingle($displayImageSrcFile, $path);

        $hoverImageSrcFile = $request->file('hoverImageSrc');
        $path = 'products/hover_images'; // Example path, adjust as needed
        $hoverImageSrc = S3Service::uploadSingle($hoverImageSrcFile, $path);

        $product = DB::table('products')->insertGetId([
            'title' => $request->title,
            'price' => $request->price,
            'discountPercent' => $request->discountPercent,
            'displayImageSrc' => $displayImageSrc ?? null,
            'hoverImageSrc' => $hoverImageSrc ?? null,
            'categoryId' => $request->categoryId,
            'subCategoryId' => $request->subCategoryId,
            'subSubCategoryId' => $request->subSubCategoryId,
            'productQuantity' => $request->productQuantity ?? 0,
            'material' => $request->material,
            'size' => $request->size,
            'capacity' => $request->capacity,
            'isFeatured' => $request->isFeatured ?? 0,
            'isBestSelling' => $request->isBestSelling ?? 0,
            'isFestiveDelights' => $request->isFestiveDelights ?? 0,
            'isRecommended' => $request->isRecommended ?? 0,
            'description' => $request->description,

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $galleryImages = $request->file('galleryImages'); // Get array of uploaded files
        if ($galleryImages && is_array($galleryImages)) { // Check if files are provided
            $path = 'products/gallery_images'; // Define storage path for gallery images

            // Call S3Service to upload multiple files
            $uploadedImages = S3Service::uploadMultiple($galleryImages, $path);

            // Insert each image into the `product_galleries` table
            foreach ($uploadedImages as $imagePath) {
                DB::table('product_galleries')->insert([
                    'productId' => $product, // Make sure $product is the product instance or its ID
                    'galleryImageSrc' => $imagePath, // Path to the individual image
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $this->sendResponse('Product created successfully', 'Product created successfully.');
    }

    // updateProduct
    public function updateProduct(Request $request, $productId)
    {

        // Check if the product exists
        $product = DB::table('products')->where('id', $productId)->first();
        if (!$product) {
            return $this->sendError('Product not found', [], 404);
        }

        // Validate category existence
        $findCategory = DB::table('categories')->where('id', $request->categoryId)->first();
        if (!$findCategory) {
            return $this->sendError('Category not found', [], 404);
        }
        if ($request->subCategoryId) {
            $findSubCategory = DB::table('sub_categories')->where('id', $request->subCategoryId)->first();
            if (!$findSubCategory) {
                return $this->sendError('Sub Category not found', [], 404);
            }
        }
        if ($request->subSubCategoryId) {
            $findSubSubCategory = DB::table('sub_sub_categories')->where('id', $request->subSubCategoryId)->first();
            if (!$findSubSubCategory) {
                return $this->sendError('Sub Sub Category not found', [], 404);
            }
        }

        // Handle display image upload
        if ($request->hasFile('displayImageSrc')) {
            $displayImageSrcFile = $request->file('displayImageSrc');
            $displayImagePath = 'products/display_images';

            // delete old image if new image is uploaded
            if ($product->displayImageSrc) {
                S3Service::deleteFile($product->displayImageSrc);
            }
            $displayImageSrc = S3Service::uploadSingle($displayImageSrcFile, $displayImagePath);
        } else {
            $displayImageSrc = $product->displayImageSrc; // Keep existing if no new image
        }

        // Handle hover image upload
        if ($request->hasFile('hoverImageSrc')) {
            $hoverImageSrcFile = $request->file('hoverImageSrc');
            $hoverImagePath = 'products/hover_images';

            // delete old image if new image is uploaded
            if ($product->hoverImageSrc) {
                S3Service::deleteFile($product->hoverImageSrc);
            }
            $hoverImageSrc = S3Service::uploadSingle($hoverImageSrcFile, $hoverImagePath);
        } else {
            $hoverImageSrc = $product->hoverImageSrc; // Keep existing if no new image
        }

        // Update product details in the products table
        DB::table('products')->where('id', $productId)->update([
            'title' => $request->title ?? $product->title,
            'price' => $request->price ?? $product->price,
            'discountPercent' => $request->discountPercent ?? $product->discountPercent,
            'displayImageSrc' => $displayImageSrc ?? $product->displayImageSrc,
            'hoverImageSrc' => $hoverImageSrc ?? $product->hoverImageSrc,
            'categoryId' => $request->categoryId ?? $product->categoryId,
            'subCategoryId' => $request->subCategoryId ?? $product->subCategoryId,
            'subSubCategoryId' => $request->subSubCategoryId ?? $product->subSubCategoryId,
            'productQuantity' => $request->productQuantity ?? $product->productQuantity,
            'material' => $request->material ?? $product->material,
            'size' => $request->size ?? $product->size,
            'capacity' => $request->capacity ?? $product->capacity,
            'isFeatured' => $request->isFeatured ?? 0,
            'isBestSelling' => $request->isBestSelling ?? 0,
            'isFestiveDelights' => $request->isFestiveDelights ?? 0,
            'isRecommended' => $request->isRecommended ?? 0,
            'description' => $request->description ?? $product->description,
            'updated_at' => now(),
        ]);

        // Handle gallery images upload and update
        if ($request->hasFile('galleryImages')) {
            // Retrieve uploaded files
            $galleryImages = $request->file('galleryImages');
            $galleryPath = 'products/gallery_images';

            // Filter only valid file uploads
            $validFiles = array_filter($galleryImages, function ($file) {
                return $file instanceof \Illuminate\Http\UploadedFile  && $file->isValid();
            });

            // Upload valid gallery images
            $uploadedImages = S3Service::uploadMultiple($validFiles, $galleryPath);

            // Insert new gallery images into the `product_galleries` table
            foreach ($uploadedImages as $imagePath) {
                DB::table('product_galleries')->insert([
                    'productId' => $productId,
                    'galleryImageSrc' => $imagePath,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $this->sendResponse('Product updated successfully', 'Product updated successfully.');
    }

// updateProductStatus
    public function updateProductStatus(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Active,Inactive',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors(), 400);
        }
        $product = DB::table('products')->where('id', $productId)->first();
        if (!$product) {
            return $this->sendError('Product not found', [], 404);
        }

        DB::table('products')->where('id', $productId)->update([
            'status' => $request->status,
            'updated_at' => now(),
        ]);

        return $this->sendResponse('Product status updated successfully', 'Product status updated successfully.');
    }

// deleteProduct
    public function deleteProduct(Request $request, $productId)
    {
        $product = DB::table('products')->where('id', $productId)->where('deleted_at', null)->first();
        if (!$product) {
            return $this->sendError('Product not found', [], 404);
        }
        // delete old image if new image is uploaded
        if ($product->displayImageSrc && $product->displayImageSrc != null) {
            S3Service::deleteFile($product->displayImageSrc);
        }

        if ($product->hoverImageSrc && $product->hoverImageSrc != null) {
            S3Service::deleteFile($product->hoverImageSrc);
        }

        // delete gallery images
        $galleryImages = DB::table('product_galleries')->where('productId', $productId)->get();
        foreach ($galleryImages as $galleryImage) {
            if ($galleryImage->galleryImageSrc) {
                S3Service::deleteFile($galleryImage->galleryImageSrc);
            }
        }

        // delete product reviews
        DB::table('product_reviews')->where('productId', $productId)->delete();

        // delete product faqs
        DB::table('ask_questions')->where('productId', $productId)->delete();

        // Delete product galleries
        DB::table('product_galleries')->where('productId', $productId)->delete();

        // Delete product
        DB::table('products')->where('id', $productId)->delete();

        return $this->sendResponse('Product deleted successfully', 'Product deleted successfully.');
    }

    // getProduct
    public function getProductReview(Request $request)
    {

        try {
            $productId = $request->productId;
            $findProducts = DB::table('products')->where('id', $productId)->
                where('deleted_at', null)->
                first();
            if (!$findProducts) {
                return $this->sendError('Product not found', [], 404);
            }
            // find product review
            $findProductReview = DB::table('product_reviews')->where('productId', $productId)->first();
            if (!$findProductReview) {
                return $this->sendError('Product review not found', [], 404);
            }

            $reviews = DB::table('product_reviews')
                ->join('users', 'product_reviews.userId', '=', 'users.id')
                ->where('product_reviews.productId', $productId) // Filter by product ID if needed
                ->select(
                    'product_reviews.rating',
                    'product_reviews.review',
                    'product_reviews.created_at as date',
                    DB::raw("CONCAT(users.firstName, ' ', users.lastName) as customerName")
                )
                ->get();

            return $this->sendResponse($reviews, 'success');
        } catch (Exception $e) {
            return $this->sendError('', $e->getMessage(), 0);
        }

    }

    // getProductFaq
    public function getProductFaq(Request $request)
    {
        try {

            $productId = $request->productId;
            $faqs = DB::table('ask_questions')
                ->where('ask_questions.productId', $productId) // Filter by product ID if needed
                ->select(
                    'ask_questions.question',
                    'ask_questions.answer'
                )
                ->get();

            return $this->sendResponse($faqs, 'Product FAQs retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError('', $e->getMessage(), 0);
        }
    }

    // getAllFaq
    public function getAllFaq(Request $request)
    {
        try {
            $faqs = DB::table('ask_questions')
                ->select(
                    'ask_questions.question',
                    'ask_questions.answer'
                )
                ->get();

            return $this->sendResponse($faqs, 'All FAQs retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError('', $e->getMessage(), 0);
        }
    }

    // deleteProductGallery   by image path
    public function deleteProductGallery(Request $request)
    {
        try {
            $path = $request->path;
            DB::table('product_galleries')->where('galleryImageSrc', $path)->delete();
            S3Service::deleteFile($path);
            return $this->sendResponse('Gallery image deleted successfully', 'Gallery image deleted successfully');
        } catch (Exception $e) {
            return $this->sendError('', $e->getMessage(), 0);
        }
    }

    // getSingleProductCustomer
    public function getSingleProductCustomer(Request $request, $productId)
    {
        // Fetch the product with its associated categories, sub-categories, and sub-sub-categories
        $singleProduct = DB::table('products')
            ->leftJoin('categories', 'products.categoryId', '=', 'categories.id')
            ->leftJoin('sub_categories', 'products.subCategoryId', '=', 'sub_categories.id')
            ->leftJoin('sub_sub_categories', 'products.subSubCategoryId', '=', 'sub_sub_categories.id')
            ->where('products.id', $productId)
            ->whereNull('products.deleted_at')
            ->select(
                'products.id as id',
                'products.title as title',
                DB::raw('CAST(products.price AS DECIMAL(10,2)) as price'),
                DB::raw('CAST(products.discountPercent AS DECIMAL(10,2)) as discountPercent'),
                'products.displayImageSrc as displayImageSrc',
                'products.hoverImageSrc as hoverImageSrc',
                'products.categoryId as categoryId',
                'products.subCategoryId as subCategoryId',
                'products.subSubCategoryId as subSubCategoryId',
                DB::raw('CAST(products.productQuantity AS UNSIGNED) as productQuantity'),
                'products.material as material',
                'products.size as size',
                'products.capacity as capacity',
                'products.isFeatured as isFeatured',
                'products.isBestSelling as isBestSelling',
                'products.isFestiveDelights as isFestiveDelights',
                'products.isRecommended as isRecommended',
                'products.description as description',
                'products.status as status',

                // Include category, sub-category, and sub-sub-category names (or null if not found)
                'categories.id as categoryId',
                'categories.name as categoryName',
                'sub_categories.id as subCategoryId',
                'sub_categories.name as subCategoryName',
                'sub_sub_categories.id as subSubCategoryId',
                'sub_sub_categories.name as subSubCategoryName'
            )
            ->first();

        // Check if the product was found
        if (!$singleProduct) {
            return $this->sendError('Product not found', [], 404);
        }

        // Fetch gallery images for the product
        $galleryImages = DB::table('product_galleries')
            ->where('productId', $productId)
            ->pluck('galleryImageSrc');

        // Fetch the category ID for the given product
        $getCatId = DB::table('products')->where('id', $productId)->pluck('categoryId')->first();

       $relatedProducts = DB::table('products')
    ->where('products.categoryId', $getCatId)
    ->where('products.id', '!=', $productId)
    ->leftJoin('categories', 'categories.id', '=', 'products.categoryId')
    ->leftJoin('sub_categories', 'sub_categories.id', '=', 'products.subCategoryId')
    ->leftJoin('sub_sub_categories', 'sub_sub_categories.id', '=', 'products.subSubCategoryId')
    ->leftJoin('product_reviews', 'product_reviews.productId', '=', 'products.id')
    ->select(
        DB::raw('CAST(products.id AS CHAR) as id'),
        'products.title',
        DB::raw('CAST(products.price AS DECIMAL(10, 2)) as price'),
        DB::raw('IFNULL(AVG(product_reviews.rating), 0) as star'),
        DB::raw('COUNT(product_reviews.id) as totalReview'),
        'products.discountPercent',
        'products.displayImageSrc',
        'products.hoverImageSrc',
        'products.productQuantity as quantity'
    )
    ->groupBy(
        'products.id',
        'products.title',
        'products.price',
        'products.discountPercent',
        'products.displayImageSrc',
        'products.hoverImageSrc',
        'products.productQuantity'
    )
    ->take(10)
    ->get();

$relatedProductsModified = $relatedProducts->map(function ($product) {
    return [
        'id' => $product->id,
        'title' => $product->title,
        'price' => (float) $product->price,
        'star' => (float) $product->star,
        'totalReview' => (int) $product->totalReview,
        'discountPercent' => (float) $product->discountPercent,
        'displayImageSrc' => $product->displayImageSrc,
        'hoverImageSrc' => $product->hoverImageSrc,
        'quantity' => (int) $product->quantity,
    ];
});


        // Prepare the final response structure
        $response = [
            'id' => (string) $singleProduct->id,
            'title' => (string) $singleProduct->title,
            'price' => (float) $singleProduct->price,
            'discountPercent' => (float) $singleProduct->discountPercent,
            'displayImageSrc' => (string) $singleProduct->displayImageSrc,
            'hoverImageSrc' => (string) $singleProduct->hoverImageSrc,
            'categoryId' => (string) $singleProduct->categoryId,
            //'categoryName' => (string) $singleProduct->categoryName,
            'subCategoryId' => (string) $singleProduct->subCategoryId,
            //'subCategoryName' => (string) $singleProduct->subCategoryName,
            'subSubCategoryId' => (string) $singleProduct->subSubCategoryId,
            // 'subSubCategoryName' => (string) $singleProduct->subSubCategoryName,
            'productQuantity' => (int) $singleProduct->productQuantity,
            'material' => (string) $singleProduct->material,
            'size' => (string) $singleProduct->size,
            'capacity' => (string) $singleProduct->capacity,
            'isFeatured' => (string) $singleProduct->isFeatured,
            'isBestSelling' => (string) $singleProduct->isBestSelling,
            'isFestiveDelights' => (string) $singleProduct->isFestiveDelights,
            'isRecommended' => (string) $singleProduct->isRecommended,
            'description' => (string) $singleProduct->description,
            'galleryImages' => $galleryImages->toArray(), // Convert collection to array
            'relatedProducts' => $relatedProductsModified,
        ];

        return $this->sendResponse($response, 'Product retrieved successfully');
    }

    /// getDynamicProduct
    public function getDynamicProduct(Request $request)
    {
        // Define the base query with joins
        $query = DB::table('products')
            ->leftJoin('categories', 'products.categoryId', '=', 'categories.id')
            ->leftJoin('sub_categories', 'products.subCategoryId', '=', 'sub_categories.id')
            ->leftJoin('sub_sub_categories', 'products.subSubCategoryId', '=', 'sub_sub_categories.id')
            ->leftJoin('product_reviews', 'products.id', '=', 'product_reviews.productId')
            ->select(
                'products.id',
                'products.title',
                DB::raw('ROUND(AVG(product_reviews.rating), 1) as star'), // Average rating
                DB::raw('COUNT(product_reviews.id) as totalReview'), // Total reviews count
                DB::raw('CAST(products.price AS DECIMAL(10,2)) as price'),
                DB::raw('CAST(products.discountPercent AS DECIMAL(10,2)) as discountPercent'),
                'products.displayImageSrc',
                'products.hoverImageSrc',
                DB::raw('CAST(products.productQuantity AS UNSIGNED) as quantity'),
                'products.isFeatured',
                'products.isBestSelling',
                'products.material',
                'products.size',
                'products.capacity',
                'products.created_at as date'
            )
            ->where('products.status', 'Active')
            ->whereNull('products.deleted_at')
              ->groupBy(
    'products.id',
    'products.title',
    'products.price',
    'products.discountPercent',
    'products.displayImageSrc',
    'products.hoverImageSrc',
    'products.productQuantity',
    'products.isFeatured',
    'products.isBestSelling',
    'products.material',
    'products.size',
    'products.capacity',
    'products.created_at'
            );
// Apply filters based on query parameters
        if ($request->filled('category')) {
            $cat = DB::table('categories')->where('name', $request->query('category'))->first();
            $banner = $cat->cover;
            $query->where('categories.name', $request->query('category'));
        }

        if ($request->filled('subCategory')) {
            $sub = DB::table('sub_categories')->where('name', $request->query(
                'subCategory'
            ))->first();
            $banner = $sub->cover ?? '';
            $query->where('sub_categories.name', $request->query('subCategory'));
        }

        if ($request->filled('subSubCategory')) {
            $subSub = DB::table('sub_sub_categories')->where('name', $request->query(
                'subSubCategory'
            ))->first();
            $banner = $subSub->cover ?? '';
            $query->where('sub_sub_categories.name', $request->query('subSubCategory'));
        }

        if ($request->filled('collection')) {
            $collection = $request->query('collection');
            if ($collection === 'New Arrivals') {
                $banner = '';
                $query->orderBy('products.created_at', 'desc'); // Latest products
            } elseif ($collection === 'Best Sellers') {
                $banner = '';
                $query->where('products.isBestSelling', '1');
            } elseif ($collection === 'Festive Delights') {
                $banner = '';
                $query->where('products.isFestiveDelights', '1'); // Assuming field exists
            }
        }

// Get filtered products
        $products = $query->get();

// Map products to the final structure
        $formattedProducts = $products->map(function ($product) {
            return [
                'id' => (string) $product->id,
                'title' => $product->title,
                'star' => (float) ($product->star ?? 0),
                'totalReview' => (int) ($product->totalReview ?? 0),
                'price' => (float) $product->price,
                'discountPercent' => (float) $product->discountPercent,
                'displayImageSrc' => $product->displayImageSrc,
                'hoverImageSrc' => $product->hoverImageSrc,
                'quantity' => (int) $product->quantity,
                'isFeatured' => (string) $product->isFeatured,
                'isBestSelling' => (string) $product->isBestSelling,
                'material' => $product->material,
                'size' => $product->size,
                'capacity' => $product->capacity,
                'date' => $product->date,
            ];
        });

// Step 2: Extract unique filter values from filtered products
        $materialFilter = $products->pluck('material')->unique()->filter()->values();

        $sizeFilter = $products->pluck('size')->unique()->filter()->values();
        $capacityFilter = $products->pluck('capacity')->unique()->filter()->values();

// Step 3: Structure the final response
        $response = [

            'banner' => $banner ?? '',
            'products' => $formattedProducts,
            'materialFilter' => $materialFilter,
            'sizeFilter' => $sizeFilter,
            'capacityFilter' => $capacityFilter,

        ];

        return $this->sendResponse($response, 'Filtered Products retrieved successfully');
    }

    // searchProduct 
    public function searchProduct(Request $request)
    {
        // Title from query string
        $keyword = $request->query('title', '');
        $keywordTrimLowerCase = strtolower(trim($keyword));
    
        // Check if the keyword is empty after trimming spaces
        if (trim($keyword) === '') {
            return $this->sendResponse([], 'No search keyword provided.');
        }

    
        $products = DB::table('products')
            ->where('products.status', 'Active')
            ->whereNull('products.deleted_at')
            ->where(function ($query) use ($keywordTrimLowerCase) {
                $query->where('title','like','%'.$keywordTrimLowerCase.
                '%');
            })
            ->leftJoin('product_reviews', 'product_reviews.productId', '=', 'products.id')
            ->select(
                'products.id',
                'products.title',
                'products.price',
                'products.displayImageSrc',
                'products.discountPercent',
                DB::raw('COUNT(product_reviews.id) as totalReview'), // Total number of reviews
                DB::raw('COALESCE(AVG(product_reviews.rating), 0) as star') // Average rating, default to 0 if no reviews
            )
          ->groupBy(
    'products.id',
    'products.title',
    'products.price',
    'products.discountPercent',
    'products.displayImageSrc'
)->get();
        // Map through products to format each product's details in the response
        $modifiedProducts = $products->map(function ($product) {
            return [
                'id' => (string) $product->id,
                'title' => $product->title,
                'star' => (float) $product->star, // Ensure star rating is a float
                'totalReview' => (int) $product->totalReview, // Ensure total review count is an integer
                'price' => (float) $product->price, // Ensure price is a float
                'displayImageSrc' => $product->displayImageSrc,
                'discountPercent' => (float) $product->discountPercent // Ensure discount percent is a float
            ];
        });
    
        // Return the modified products in a structured response
        return $this->sendResponse($modifiedProducts, 'Search results');
    }
    
      // get all discount able products
    public function getDiscountAbleProducts()
    {
        $products = DB::table('products')->where('discountPercent', '>', 0)->get();
        return $this->sendResponse($products, 'Discount able products');
    }
    

}
