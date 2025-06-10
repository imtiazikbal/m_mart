<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class HomeController extends ResponseController
{
    public function getHeaderCategories()
    {
        // Fetch only active categories with showInHeaderBar = 1 and their associated active subcategories and sub-subcategories
        $categories = DB::table('categories')
            ->where('categories.showInHeaderBar', '1')  // Only categories shown in header
            ->where('categories.status', 'Active')  // Only active categories
            ->leftJoin('sub_categories', function ($join) {
                $join->on('sub_categories.categoryId', '=', 'categories.id')
                     ->where('sub_categories.status', 'Active');  // Only active subcategories
            })
            ->leftJoin('sub_sub_categories', function ($join) {
                $join->on('sub_sub_categories.subCategoryId', '=', 'sub_categories.id')
                     ->where('sub_sub_categories.status', 'Active');  // Only active sub-sub-categories
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


    // getHeaderFooter 
    public function getHeaderFooter()
    {
  $footerCategories = DB::table('landing_pages_info')
  ->select('footerCategories')
  ->get();
  
  // Process and map over the retrieved data
$landingPageInfo = $footerCategories->map(function ($item) {
  // Decode the footerCategories JSON string into an array
  $footerCategoryIds = json_decode($item->footerCategories, true);

  // Ensure that the decoded value is an array and not empty
  if (is_array($footerCategoryIds) && !empty($footerCategoryIds)) {
      // Retrieve category names based on the decoded category IDs
      $categoryNames = DB::table('categories')
      ->whereIn('id', $footerCategoryIds)
      ->pluck('name') // Get only the 'name' column of categories
      ->toArray();
      
      // Assign the category names to the footerCategories attribute
      $item->footerCategories = $categoryNames;
    } else {
        // If the decoded value is not an array or is empty, set footerCategories to an empty array
        $item->footerCategories = [];
    }
    
    return $item;
});



$landingPageInfo = DB::table('landing_pages_info')->first();


// Define an array of social media links with default empty strings
$socialLinksArray = [
    'Facebook' => '',
    'LinkedIn' => '',
    'Instagram' => '',
    'Pinterest' => '',
];

// If `landingPageInfo` exists, check for social media links and update accordingly
if ($landingPageInfo) {
    $socialLinksArray['Facebook'] = $landingPageInfo->facebook ?? '';
    $socialLinksArray['LinkedIn'] = $landingPageInfo->linkedin ?? '';
    $socialLinksArray['Instagram'] = $landingPageInfo->instagram ?? '';
    $socialLinksArray['Pinterest'] = $landingPageInfo->pinterest ?? '';
}

// Format `socialLinksArray` into the desired structure
$socialLinksFormatted = [];
foreach ($socialLinksArray as $title => $link) {
    $socialLinksFormatted[] = [
        'title' => $title,
        'link' => $link
    ];
}

$response = [
    'notice' => $landingPageInfo->notice ?? null,
    'logo' => $landingPageInfo->logo ?? null,
    'favicon' => $landingPageInfo->favicon ?? null,
    'header' => $this->getHeaderCategoriesForGet(),
    'footer' => [
        'companyName'=> $landingPageInfo->companyName ?? null,
        'companyEmail'=> $landingPageInfo->email ?? null,
        'companyPhone'=> $landingPageInfo->phone ?? null,
        'companyAddress'=> $landingPageInfo->address ?? null,
        'copyrightText' => $landingPageInfo->copyrightText ?? null,
'socialLinks' => $socialLinksFormatted,
'footerCategories' => $footerCategories->first()->footerCategories ?? [],
    ],

];

return $this->sendResponse($response, 'Data retrieved successfully.');
    }




    // getLandingPage 
public function getLandingPage(){
                        $categoryCarousel = DB::table('categories_carousel')
                        ->leftJoin('categories', 'categories_carousel.categoryId', '=', 'categories.id')
                        ->select(
                            'categories_carousel.*',
                            'categories.name as categoryName',
                            'categories.id as categoryId'
                        )
                        ->where('categories_carousel.status', 'Active') // Specify the table name for 'status'
                        ->get();
                    
                    $modifiedCategoryCarousel = $categoryCarousel->map(function ($category) {
                        return [
                            'id' => (string) $category->id,
                            'isVideo' => $category->isVideo == 1 ? true : false,
                            'src' => $category->src,
                            'categoryName' => $category->categoryName,
                        ];
                    });
                    

                        // heroCategory 
                        $heroCategory = DB::table('categories')
                                        ->where('showInIconBar', '1')
                                        ->whereNull('deleted_at')
                                        ->where('status', 'Active')
                                        ->select('id', 'name','icon')
                                                ->get();

                        $modifiedHeroCategory = $heroCategory->map(function ($category) {
                            return [
                                'id' => (string) $category->id,
                                'categoryName' => $category->name,
                                'icon' => $category->icon,
                            ];
                        });


                        // Top Banner
                        $topBanner = DB::table('landing_pages_info')->select(
                            'topBanner'
                        )
                        ->first();


                    // Fetch 10 latest products with their average rating and review count
               $newArrivalProducts = DB::table('products')
    ->whereNull('products.deleted_at')
    ->where('products.status', 'Active')
    ->leftJoin('product_reviews', 'products.id', '=', 'product_reviews.productId')
    ->select(
        'products.id',
        'products.title',
        'products.price',
        'products.discountPercent',
        'products.displayImageSrc',
        'products.hoverImageSrc',
        'products.productQuantity',
        DB::raw('ROUND(AVG(product_reviews.rating), 1) as averageRating'),
        DB::raw('COUNT(product_reviews.id) as reviewsCount')
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
    ->orderBy('products.id', 'desc')
    ->limit(10)
    ->get();




                                $productCarousel = DB::table('products_carousel')
                                ->leftJoin('products', 'products_carousel.productId', '=', 'products.id')
                                ->select(
                                    'products_carousel.*',
                                    'products.title as productName',
                                    'products.id as productId'
                                )
                                ->where('products_carousel.status', 'Active')
                                ->get();
                                $modifiedProductCarousel = $productCarousel->map(function ($item) {
                                    return [
                                        'id' => (string) $item->id,
                                        'productId' => (string) $item->productId,
                                        'productName' =>  $item->productName,
                                        'imageSrc' =>  $item->imageSrc,
                                    ];
                                });


                                // productCategory 
                                $productCategory = DB::table('categories')
                                ->where('showInProductBar', '1')
                                ->where('status', 'Active')
                                ->whereNull('deleted_at')
                                ->select('id', 'name','thumbnail')
                                        ->get();

                                $modifiedProductCategory = $productCategory->map(function ($category) {
                                    return [
                                        'id' => (string) $category->id,
                                        'categoryName' => $category->name,
                                        'thumbnail' => $category->thumbnail,
                                    ];
                                });

                                $bottomBanner= DB::table('landing_pages_info')->select(
                                    'bottomBanner'
                                )
                                ->first();




                                // Fetch 10 recommended products with their average rating and review count
               $recommendedProducts = DB::table('products')
    ->whereNull('products.deleted_at')
    ->where('products.status', 'Active')
    ->where('isRecommended', '1')
    ->leftJoin('product_reviews', 'products.id', '=', 'product_reviews.productId')
    ->select(
        'products.id',
        'products.title',
        'products.price',
        'products.discountPercent',
        'products.displayImageSrc',
        'products.hoverImageSrc',
        'products.productQuantity',
        DB::raw('ROUND(AVG(product_reviews.rating), 1) as averageRating'),
        DB::raw('COUNT(product_reviews.id) as reviewsCount')
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
    ->orderBy('products.id', 'desc')
    ->limit(10)
    ->get();

$recommendedProductsModified = $recommendedProducts->map(function ($product) {
    return [
        'id' => (string) $product->id,
        'title' => $product->title,
        'price' => (float) $product->price,
        'star' => (float) $product->averageRating,
        'totalReview' => (int) $product->reviewsCount,
        'discountPercent' => (float) $product->discountPercent,
        'displayImageSrc' => $product->displayImageSrc,
        'hoverImageSrc' => $product->hoverImageSrc,
        'quantity' => (int) $product->productQuantity,
    ];
});




                    // Fetch 10 festiveDelightProducts products with their average rating and review count
                   $festiveDelightProducts = DB::table('products')
    ->whereNull('products.deleted_at')
    ->where('products.status', 'Active')
    ->where('isFestiveDelights', '1')
    ->leftJoin('product_reviews', 'products.id', '=', 'product_reviews.productId')
    ->select(
        'products.id',
        'products.title',
        'products.price',
        'products.discountPercent',
        'products.displayImageSrc',
        'products.hoverImageSrc',
        'products.productQuantity',
        DB::raw('ROUND(AVG(product_reviews.rating), 1) as averageRating'),
        DB::raw('COUNT(product_reviews.id) as reviewsCount')
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
    ->orderBy('products.id', 'desc')
    ->limit(10)
    ->get();

                    
                                        $festiveDelightProductsModified = $festiveDelightProducts->map(function ($product) {
                                    return [
                                        'id' => (string) $product->id,
                                        'title' => $product->title,
                                        'price' => (float) $product->price,
                                        'star' => (float) $product->averageRating,
                                        'totalReview' => (int) $product->reviewsCount,
                                        'discountPercent' => (float) $product->discountPercent,
                                        'displayImageSrc' => $product->displayImageSrc,
                                        'hoverImageSrc' => $product->hoverImageSrc,
                                        'quantity' => (int) $product->productQuantity
                                    ];
                                });




                                // happyCustomers 
                                $happyCustomers = DB::table('product_reviews')
                                ->leftJoin('products', 'product_reviews.productId', '=', 'products.id')
                                ->leftJoin('users', 'product_reviews.userId', '=', 'users.id')
                                ->select(
                                    'product_reviews.id as id',
                                    'product_reviews.reviewImageSrc as reviewImageSrc',
                                    DB::raw('CONCAT(users.firstName, " ", users.lastName) as customerName'), 
                                    'product_reviews.review as review',
                                    'product_reviews.rating as rating',
                                    'product_reviews.productId as productId',
                                    'products.title as productTitle',
                                    'products.displayImageSrc as productThumbnailSrc',
                                )
                                ->get();

                                $happyCustomersModified = $happyCustomers->map(function ($customer) {
                                    return [
                                        'id' => (string) $customer->id,
                                        'reviewImageSrc' => $customer->reviewImageSrc,
                                        'customerName' => $customer->customerName,
                                        'review' => $customer->review,
                                        'rating' => (float) $customer->rating,
                                        'productId' => (string) $customer->productId,
                                        'productTitle' => $customer->productTitle,
                                        'productThumbnailSrc' => $customer->productThumbnailSrc
                                    ];
                                });


                                // bestSellingProducts 

                // Fetch 10 bestSellingProducts products with their average rating and review count
                $bestSellingProducts = DB::table('products')
    ->whereNull('products.deleted_at')
    ->where('products.status', 'Active')
    ->where('isBestSelling', '1')
    ->leftJoin('product_reviews', 'products.id', '=', 'product_reviews.productId')
    ->select(
        'products.id',
        'products.title',
        'products.price',
        'products.discountPercent',
        'products.displayImageSrc',
        'products.hoverImageSrc',
        'products.productQuantity',
        DB::raw('ROUND(AVG(product_reviews.rating), 1) as averageRating'),
        DB::raw('COUNT(product_reviews.id) as reviewsCount')
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
    ->orderBy('products.id', 'desc')
    ->limit(10)
    ->get();

$bestSellingProductsModified = $bestSellingProducts->map(function ($product) {
    return [
        'id' => (string) $product->id,
        'title' => $product->title,
        'price' => (float) $product->price,
        'star' => (float) $product->averageRating,
        'totalReview' => (int) $product->reviewsCount,
        'discountPercent' => (float) $product->discountPercent,
        'displayImageSrc' => $product->displayImageSrc,
        'hoverImageSrc' => $product->hoverImageSrc,
        'quantity' => (int) $product->productQuantity
    ];
});


                            $reponse = [
                                'productCarousel' => $modifiedProductCarousel ?? null,
                                'heroCategorys' => $modifiedHeroCategory ?? null,
                                'topBanner' => $topBanner->topBanner ?? null,
                                'newArrivalProducts' => $newArrivalProductsModified ?? null,
                                'categoryCarousel' => $modifiedCategoryCarousel ?? null,
                                'productCategorys' => $modifiedProductCategory ?? null,
                                'bottomBanner' => $bottomBanner->bottomBanner ?? null,
                                'recommendedProducts' => $recommendedProductsModified ?? null,
                                'festiveDelightProducts' => $festiveDelightProductsModified ?? null,
                                'happyCustomers' => $happyCustomersModified ?? null,
                                'bestSellingProducts' => $bestSellingProductsModified ?? null
                            ];

                            
                        return $this->sendResponse($reponse, 'Landing page data retrieved successfully.');
                    }




                    // private header categories 
                     private function getHeaderCategoriesForGet()
    {
        // Fetch only active categories with showInHeaderBar = 1 and their associated active subcategories and sub-subcategories
        $categories = DB::table('categories')
            ->where('categories.showInHeaderBar', '1')  // Only categories shown in header
            ->where('categories.status', 'Active')  // Only active categories
            ->whereNull('categories.deleted_at')
            ->leftJoin('sub_categories', function ($join) {
                $join->on('sub_categories.categoryId', '=', 'categories.id')
                     ->where('sub_categories.status', 'Active')  // Only active subcategories
                     ->whereNull('sub_categories.deleted_at');
            })
            ->leftJoin('sub_sub_categories', function ($join) {
                $join->on('sub_sub_categories.subCategoryId', '=', 'sub_categories.id')
                     ->where('sub_sub_categories.status', 'Active')  // Only active sub-sub-categories
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
        return $responseData;
    }
   
    
}


