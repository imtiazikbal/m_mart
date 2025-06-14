<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\v1\AuthController;
use App\Http\Controllers\api\v1\HomeController;
use App\Http\Controllers\api\v1\AboutController;
use App\Http\Controllers\api\v1\AdminController;
use App\Http\Controllers\api\v1\OrderController;
use App\Http\Controllers\api\v1\CouponController;
use App\Http\Controllers\api\v1\NavBarController;
use App\Http\Controllers\api\v1\AddressController;
use App\Http\Controllers\api\v1\ContactController;
use App\Http\Controllers\api\v1\PaymentController;
use App\Http\Controllers\api\v1\PrivacyController;
use App\Http\Controllers\api\v1\ProductController;
use App\Http\Controllers\api\v1\CategoryController;
use App\Http\Controllers\api\v1\SettingsController;
use App\Http\Controllers\api\v1\DashboardController;
use App\Http\Controllers\api\v1\SubscribeController;
use App\Http\Controllers\api\v1\SubCategoryController;
use App\Http\Controllers\api\v1\TopCategoryController;
use App\Http\Controllers\api\v1\VideoBannerController;
use App\Http\Controllers\api\v1\ReturnPolicyController;
use App\Http\Controllers\api\v1\ProductReviewController;
use App\Http\Controllers\api\v1\RefundPrivacyController;
use App\Http\Controllers\api\v1\BottomCategoryController;
use App\Http\Controllers\api\v1\CategoryBannerController;
use App\Http\Controllers\api\v1\QuestionAnswerController;
use App\Http\Controllers\api\v1\SubSubCategoryController;
use App\Http\Controllers\api\v1\TermsConditionController;
use App\Http\Controllers\api\v1\LandingPageInfoController;
use App\Http\Controllers\api\v1\ProductCarouselController;
use App\Http\Controllers\api\v1\CategoryCarouselController;
use App\Http\Controllers\api\v1\ShippingDeliveryController;
use App\Http\Controllers\api\v1\RefundCencellationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');




// Admin routes
Route::group(['prefix' => 'v1'], function () {
    
    Route::post('/admin/signup', [AdminController::class,'signup']);
    Route::post('/admin/login', [AdminController::class,'login']);
    Route::get('admin', [AdminController::class,'tokenVarificationForAdmin'])->middleware('api.auth');
    Route::post('/admin/auth/verify', [AdminController::class,'verifyOtp']);


});


// Custom routes
Route::group(['prefix' => 'v1'], function () {
    Route::post('/customer/auth', [AuthController::class,'login']);
    Route::post('/customer/auth/verify', [AuthController::class,'verifyOtp']);
    Route::get('/customer/auth', [AuthController::class,'tokenVarification'])->middleware('api.auth');

    // get all users
    Route::get('admin/all/customer', [AuthController::class,'getAllUsers']);

    // user status update
    Route::patch('admin/customer/status/{id}', [AuthController::class,'updateUserStatus']);



    // Update Username by token
    Route::patch('/customer/username', [AuthController::class,'updateUsername'])->middleware('api.auth');

    // Customer address add
    Route::post('/customer/address', [AddressController::class,'storeAddress'])->middleware('api.auth');
    // Customer address update by id
    Route::patch('/customer/address/{id}', [AddressController::class,'updateAddress'])->middleware('api.auth');
    // get all customer address
    Route::get('/customer/address', [AddressController::class,'getAddress'])->middleware('api.auth');

    // Get profile by token
    Route::get('/customer/profile', [AuthController::class,'getProfile'])->middleware('api.auth');
    // Address delete by id
    Route::delete('/customer/address/{id}', [AddressController::class,'deleteAddress'])->middleware('api.auth');
});



// Product routes
Route::group(['prefix'=> 'v1'], function () {
    //Store Product
    Route::post('/product', [ProductController::class,'storeProduct'])->middleware('api.auth');
    // Update Product
    Route::post('/product/{id}', [ProductController::class,'updateProduct'])->middleware('api.auth');
    // Update Product Status
    Route::patch('/product/status/{id}', [ProductController::class,'updateProductStatus'])->middleware('api.auth');
    // Delete Product
    Route::delete('/product/{id}', [ProductController::class,'deleteProduct'])->middleware('api.auth');
    // get all products
    Route::get('/product', [ProductController::class,'getAllProduct'])->middleware('api.auth');
    // get single product
    Route::get('/product/{id}', [ProductController::class,'getSingleProduct']);

    // get all products for admin
    Route::get('/admin/product', [ProductController::class,'getAllProductForAdmin']);

    // get single product for admin
    Route::get('/admin/product/{id}', [ProductController::class,'getSingleProductForAdmin']);


    // Product gallery Image delete
    Route::delete('/product/gallery/delete', [ProductController::class,'deleteProductGallery']);

    




    Route::get('/product/review/{productId}', [ProductController::class,'getProductReview']);

    //get all faq by product id
    Route::get('/product/faq/{productId}', [ProductController::class,'getProductFaq']);
    // Get all faq
    Route::get('/all/product/faq', [ProductController::class,'getAllFaq']);

    // get discounted product
    Route::get('/discounted/product', [ProductController::class,'getDiscountAbleProducts']);




    // single product get for customer
    Route::get('/customer/product/{productId}', [ProductController::class,'getSingleProductCustomer']);



    // dynamic product get cat sub sub cat from query param
    Route::get('/filter/product', [ProductController::class,'getDynamicProduct']);

    // product search by title
    Route::get('/search/product', [ProductController::class,'searchProduct']);

    // get latest collections
    Route::get('/latest/collection', [ProductController::class,'getLatestCollections']);
    // latest 8 products
    Route::get('/latest/product', [ProductController::class,'getLatestEightProducts']);

    // unique size product by query cat and sub 
    Route::get('/unique/size/product', [ProductController::class,'getUniqueSizeProduct']);

    // product list
    Route::get('/query/product/list', [ProductController::class,'getProductList']);

});




// Prduct review routes
Route::group(['prefix'=> 'v1'], function () {

    Route::post('/product/review/store', [ProductReviewController::class,'storeReview'])->middleware('api.auth');

    // Get All Product Review
    Route::get('/all/product/review', [ProductReviewController::class,'getAllReview1']);

    //delete review by id
    Route::delete('/product/review/delete/{id}', [ProductReviewController::class,'deleteReview']);
});


// Question and Answer route here


// Prduct faq routes
Route::group(['prefix'=> 'v1'], function () {
    Route::post('/customer/question', [QuestionAnswerController::class,'storeQuestion'])->middleware('api.auth');

    // Answer the question
    Route::post('/customer/answer', [QuestionAnswerController::class,'answerQuestion'])->middleware('api.auth');

   

     // delete faq
     Route::delete('/product/faq/delete/{id}', [QuestionAnswerController::class,'deleteFaq']);


     /// get all questions list // for admin panel 
     Route::get('/admin/question', [QuestionAnswerController::class,'getAllQuestionForAdmin']);
});




// Dashboard routes here

// Prduct review routes
Route::group(['prefix'=> 'v1'], function () {
   
    // Answer the question
    Route::post('/customer/answer', [QuestionAnswerController::class,'answerQuestion']);
    // get all questions list 
    Route::get('/all/customer/question', [QuestionAnswerController::class,'getAllQuestion'])->middleware('api.auth');
});




// Category routes here
Route::group(['prefix'=> 'v1'], function () {
    // Store Category
    Route::post('/category', [CategoryController::class,'storeCategory'])->middleware('api.auth');
     // Update Category
     Route::post('/category/{id}', [CategoryController::class,'updateCategory'])->middleware('api.auth');
     // Update category Status
     Route::patch('/category/status/{id}', [CategoryController::class,'updateCategoryStatus'])->middleware('api.auth');
     // Delete Category
     Route::delete('/category/{id}', [CategoryController::class,'deleteCategory'])->middleware('api.auth');
    // get all categories
    Route::get('/category', [CategoryController::class,'getAllCategory'])->middleware('api.auth');

    // Gel all category sub sub sub cat for admin 
    Route::get('/admin/product/allcategory', [CategoryController::class,'getAllCategoryForAdmin']);
});




// SubCategory routes here
Route::group(['prefix'=> 'v1'], function () {
    // Store Category
    Route::post('/subcategory', [SubCategoryController::class,'storeSubCategory'])->middleware('api.auth');
     // Update Category
     Route::post('/subcategory/{id}', [SubCategoryController::class,'updateSubCategory'])->middleware('api.auth');
     // Update category Status
     Route::patch('/subcategory/status/{id}', [SubCategoryController::class,'updateSubCategoryStatus'])->middleware('api.auth');
     // Delete Category
     Route::delete('/subcategory/{id}', [SubCategoryController::class,'deleteSubCategory'])->middleware('api.auth');
    // get all categories
    Route::get('/subcategory', [SubCategoryController::class,'getAllSubCategory'])->middleware('api.auth');
       // getSubCategoriesByCategoryId
    Route::get('/subcategories/{categoryId}', [SubCategoryController::class,'getSubCategoriesByCategoryId']);

});





// SubSubCategory routes here
Route::group(['prefix'=> 'v1'], function () {
    // Store Category
    Route::post('/subsubcategory', [SubSubCategoryController::class,'storeSubSubCategory'])->middleware('api.auth');
     // Update Category
     Route::post('/subsubcategory/{id}', [SubSubCategoryController::class,'updateSubSubCategory'])->middleware('api.auth');
     // Update category Status
     Route::patch('/subsubcategory/status/{id}', [SubSubCategoryController::class,'updateSubSubCategoryStatus'])->middleware('api.auth');
     // Delete Category
     Route::delete('/subsubcategory/{id}', [SubSubCategoryController::class,'deleteSubSubCategory'])->middleware('api.auth');
    // get all categories
    Route::get('/subsubcategory', [SubSubCategoryController::class,'getAllSubSubCategory'])->middleware('api.auth');
 });






/// Fontend routes here



// SubSubCategory routes here
Route::group(['prefix'=> 'v1'], function () {
    // Header Category
    // Route::get('/header/category', [HomeController::class,'getHeaderCategories']);

    //  get header-footer 
    Route::get('/header/footer', [HomeController::class,'getHeaderFooter']);

    // landing page all info
    Route::get('/landingpage', [HomeController::class,'getLandingPage']);
    });



// Conatc us routes here
Route::group(['prefix'=> 'v1'], function () {
    // Contact us
    Route::post('/contact', [ContactController::class,'contactUs']);
    // Contact us
    Route::get('/contact', [ContactController::class,'getAllContact']);
    // delete contact us
    Route::delete('/contact/{id}', [ContactController::class,'deleteContact']);
    });





    // subscription routes here
    Route::group(['prefix'=> 'v1'], function () {
        // subscription
        Route::post('/subscribe', [SubscribeController::class,'subscribe']);

        // get all subscribers
        Route::get('/subscribers', [SubscribeController::class,'getAllSubscribers']);

        // getAllSubscribers
        Route::delete('/subscribe/{id}', [SubscribeController::class,'deleteSubscribe']);
        
        });



       /// Categories carousel routes here

       Route::group(['prefix'=> 'v1'], function () {
        // store category carousel
        Route::post('/store/category/carousel', [CategoryCarouselController::class,'storeCategoryCarousel']);
        // update category carousel
        Route::patch('/update/category/carousel/{id}', [CategoryCarouselController::class,'updateCategoryCarousel']);
        // delete category carousel
        Route::delete('/delete/category/carousel/{id}', [CategoryCarouselController::class,'deleteCategoryCarousel']);
        // get all category carousel
        Route::get('/category/carousel', [CategoryCarouselController::class,'getAllCategoryCarousel']);
           
       });


       // product carousel routes here
       Route::group(['prefix'=> 'v1'], function () {
        // store product carousel
        Route::post('/store/product/carousel', [ProductCarouselController::class,'storeProductCarousel']);
        // update product carousel
        Route::patch('/update/product/carousel/{id}', [ProductCarouselController::class,'updateProductCarousel']);
        // delete product carousel
        Route::delete('/delete/product/carousel/{id}', [ProductCarouselController::class,'deleteProductCarousel']);
        // get all product carousel
        Route::get('/all/product/carousel', [ProductCarouselController::class,'getAllProductCarousel']);
           
       });


       // terms and condition routes here
       Route::group(['prefix'=> 'v1'], function () {
        // store terms and condition
        Route::post('/store/terms/condition', [TermsConditionController::class,'storeTermsAndCondition']);
        // get all terms and condition
        Route::get('/terms/condition', [TermsConditionController::class,'getAllTermsAndCondition']);  
       });


         // Privacy policy routes here
         Route::group(['prefix'=> 'v1'], function () {
            // store terms and condition
            Route::post('/store/privacy/policy', [PrivacyController::class,'storePrivacyPolicy']);
            // get all terms and condition
            Route::get('/privacy/policy', [PrivacyController::class,'getAllPrivacyPolicy']);  
           });

            // Refund policy routes here
         Route::group(['prefix'=> 'v1'], function () {
            // store terms and condition
            Route::post('/store/refund/policy', [RefundPrivacyController::class,'storeRefundPolicy']);
            // get all terms and condition
            Route::get('/refund/policy', [RefundPrivacyController::class,'getRefundPolicy']);  
           });

            // Shipping delivery routes here
         Route::group(['prefix'=> 'v1'], function () {
            // store terms and condition
            Route::post('/store/shipping/delivery', [ShippingDeliveryController::class,'storeShippingDelivery']);
            // get all terms and condition
            Route::get('/shipping/delivery', [ShippingDeliveryController::class,'getShippingDelivery']);  
           });


             // Refund Cencellation routes here
         Route::group(['prefix'=> 'v1'], function () {
            // store terms and condition
            Route::post('/store/refund/cancellation', [RefundCencellationController::class,'storeRefundCencellation']);
            // get all terms and condition
            Route::get('/refund/cancellation', [RefundCencellationController::class,'getRefundCencellation']);  
           });



           // Landing page info routes here
           Route::group(['prefix'=> 'v1'], function () {
            // store terms and condition
            Route::post('/store/landing/page/info', [LandingPageInfoController::class,'storeLandingPageInfo']);
            // get all terms and condition
            Route::get('/landing/page/info', [LandingPageInfoController::class,'getLandingPageInfo']);
           });
           


           // About us routes here
           Route::group(['prefix'=> 'v1'], function () {
            // store terms and condition
            Route::post('/store/about', [AboutController::class,'storeAbout']);
            // get all terms and condition
            Route::get('/about', [AboutController::class,'getAbout']);
           });


           // About us routes here
           Route::group(['prefix'=> 'v1'], function () {
            // store terms and condition
            Route::post('/store/return/policy', [ReturnPolicyController::class,'storeReturnPolicy']);
            // get all terms and condition
            Route::get('/return/policy', [ReturnPolicyController::class,'getReturnPolicy']);
           });



           // Payment routes here
           Route::group(['prefix'=> 'v1'], function () {
            // store terms and condition
            Route::post('/payment/initiate', [PaymentController::class,'storePayment'])->middleware('api.auth');
            // get all terms and condition
            Route::get('/payment', [PaymentController::class,'getPayment']);
            // success route
            Route::get('/stripe/success', [PaymentController::class,'success'])->name('stripe.success');
            Route::get('/stripe/cancel', [PaymentController::class,'cancel'])->name('stripe.cancel');
            Route::get('/stripe/failed', [PaymentController::class,'failed'])->name('stripe.failed');
           });


           // setings 
           Route::group(['prefix'=> 'v1'], function () {
            // store terms and condition
            Route::post('/store/setting', [SettingsController::class,'storeSetting']);
            // get all terms and condition
            Route::get('/setting', [SettingsController::class,'getSetting'])->middleware('api.auth');
           });



           // Coupon controller 
           Route::group(['prefix'=> 'v1'], function () {
            // store terms and condition
            Route::post('/store/coupon', [CouponController::class,'storeCoupon']);
            // Update coupon
            Route::patch('update/coupon/{id}', [CouponController::class,'updateCoupon']);

            // couson status update 
            Route::patch('coupon/status/update/{id}', [CouponController::class,'statusUpdate']);
            
            // get all terms and condition
            Route::get('/coupon', [CouponController::class,'getCoupon']);

            // coupons for customers
            Route::get('/customer/coupon', [CouponController::class,'getCustomerCoupon']);

            // delete coupon
            Route::delete('delete/coupon/{id}', [CouponController::class,'deleteCoupon']);
           });




           // Order routes here
           Route::group(['prefix'=> 'v1'], function () {
            Route::get('/orders', [OrderController::class,'getOrder']);

            // get single order details
            Route::get('/order/{id}', [OrderController::class,'getSingleOrder']);

            // update order status
            Route::patch('order/status/update/{id}', [OrderController::class,'statusUpdate']);


            // get order list by token (for customer)
            Route::get('/get/order/customer', [OrderController::class,'getOrderListByToken'])->middleware('api.auth');

            // order tracking by order id or phone number 
            Route::get('/order/tracking/{id}', [OrderController::class,'orderTracking']);
           });



           // Dashboard routes here
           Route::group(['prefix'=> 'v1'], function () {
            // get all terms and condition
            Route::get('/dashboard', [DashboardController::class,'getDashboard']);
           });




        // Top category routes here
           Route::group(['prefix'=> 'v1'], function () {
            Route::post('/top/category', [TopCategoryController::class,'store']);
            Route::post('/top/category/{id}', [TopCategoryController::class,'update']);
            Route::get('/top/category', [TopCategoryController::class,'getAll']);
            Route::delete('/top/category/{id}', [TopCategoryController::class,'delete']);

            // frontend 
            Route::get('/top/category/frontend', [TopCategoryController::class,'getAllForFrontend']);

           });


            // Bottom category routes here
           Route::group(['prefix'=> 'v1'], function () {
            Route::post('/bottom/category', [BottomCategoryController::class,'store']);
            Route::post('/bottom/category/{id}', [BottomCategoryController::class,'update']);
            Route::get('/bottom/category', [BottomCategoryController::class,'getAll']);
            Route::delete('/bottom/category/{id}', [BottomCategoryController::class,'delete']);
            // frontend
            Route::get('/bottom/category/frontend', [BottomCategoryController::class,'getAllForFrontend']);

           });

              //  category banner routes here
           Route::group(['prefix'=> 'v1'], function () {
            Route::post('/banner/category', [CategoryBannerController::class,'store']);
            Route::get('/banner/category', [CategoryBannerController::class,'getAll']);
            // frontend
            Route::get('/banner/category/frontend', [CategoryBannerController::class,'getAllForFrontend']);

           });


              //  Video banner routes here
           Route::group(['prefix'=> 'v1'], function () {
            Route::post('/video/banner', [VideoBannerController::class,'store']);
            Route::get('/video/banner', [VideoBannerController::class,'getAll']);
            // frontend
            Route::get('/video/banner/frontend', [VideoBannerController::class,'getAllForFrontend']);

           });





            /// Nav bar routes here
           Route::group(['prefix'=> 'v1'], function () {
            Route::get('/header/category', [NavBarController::class,'getAllNavItems']);
           });


             ///Category carousel routes here
           Route::group(['prefix'=> 'v1'], function () {
            Route::get('/header/category/slider', [CategoryCarouselController::class,'getAllCategoryCarouselForFrontend']);
           });






           // SSL Commerz
            Route::post('/PaymentSuccess', [PaymentController::class, 'PaymentSuccess'])->name('ssl.success');
            Route::post('/PaymentFail', [PaymentController::class, 'PaymentFail'])->name('ssl.fail');
            Route::post('/PaymentCancel', [PaymentController::class, 'PaymentCancel'])->name('ssl.cancel');