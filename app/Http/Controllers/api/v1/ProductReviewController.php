<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ProductReviewController extends ResponseController
{
    // storeReview 
    public function storeReview(Request $request)
    {
       try{

       
        $validator = Validator::make($request->all(), [
            'productId' => 'required',
            'review' => 'required',
            'rating' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
        }

        $findProduct = DB::table('products')->where('id', $request->productId)->first();
        if (!$findProduct) {
            return $this->sendError('Product not found', [], 404);
        }
        $userId = $request->headers->get('userID');

        $userFind = DB::table('users')->where('id', $userId)->first();
        if (!$userFind) {
            return $this->sendError('User not found', [], 404);
        }


        if($request->hasFile('reviewImageSrc')){
             // Get the uploaded file
         $file = $request->file('reviewImageSrc');
        
         // Define the path where you want to store the file
         $path = 'product_reviews/images'; // Example path, adjust as needed
 
         // Use the UploadService to upload the file to S3
         $url = S3Service::uploadSingle($file, $path);
        }

        DB::table('product_reviews')->insert([
            'userId'=> $userId,
            'productId' => $request->productId,
            'rating'=> $request->rating,
            'review'=> $request->review,
            'reviewImageSrc'=> $url ?? null,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        return $this->sendResponse('Product review added successfully', 'Review added successfully');
       }catch(Exception $e){
        return $this->sendError('', $e->getMessage(),0);
       }
    }


    // getAllReview
    public function getAllReview1(Request $request)
    {
        try{
    
    $reviews = DB::table('product_reviews')
    ->join('users', 'product_reviews.userId', '=', 'users.id')
    ->join('products', 'product_reviews.productId', '=', 'products.id')
    ->select(
        'product_reviews.id',
        'product_reviews.productId',
        'product_reviews.rating',
        'product_reviews.review',
        'product_reviews.reviewImageSrc',
        'products.title as productTitle',
        'products.displayImageSrc as productThumbnailSrc',
        DB::raw("CONCAT(users.firstName, ' ', users.lastName) as customerName"),
        'product_reviews.created_at as date'
    )
    ->get();

    return $this->sendResponse($reviews, 'Product reviews fetched successfully');
        }catch(Exception $e){
            return $this->sendError('', $e->getMessage());
        }
}

// deleteReview
public function deleteReview(Request $request,$id){
    try{
        $findReview = DB::table('product_reviews')->where('id', $id)->first();
        if (!$findReview) {
            return $this->sendError('Review not found', [], 404);
        }
        DB::table('product_reviews')->where('id', $id)->delete();
        return $this->sendResponse('Review deleted successfully', 'Review deleted successfully');
    }catch(Exception $e){
        return $this->sendError('', $e->getMessage(),0);
    }
}
}
