<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ProductCarouselController extends ResponseController
{
    // storeProductCarousel 
    public function storeProductCarousel(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'imageSrc' => 'required',
                'productId' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            // find product
            $findProduct = DB::table('products')->where('id', $request->productId)->first();
            if (!$findProduct) {
                return $this->sendError('Product not found', [], 404);
            }

            // store image in s3
          if($request->imageSrc){
            $file = $request->file('imageSrc');
            $path = 'product-carousel'; // Example path, adjust as needed
            $productCarouselUrl = S3Service::uploadSingle($file, $path);
          }
            
            DB::table('products_carousel')->insert([
                'productId' => $request->productId,
                'imageSrc' => $productCarouselUrl,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return $this->sendResponse('Product Carousel created successfully', 'Product Carousel created successfully');
        }catch(Exception $e){
            return $this->sendError($e->getMessage(), $e->getCode());
        }
    }
    // updateProductCarousel status 
    public function updateProductCarousel(Request $request, $id){
        try{
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:Active,Inactive'
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation error', $validator->errors(), 400);
            }

            $findProductCarousel = DB::table('products_carousel')->where('id', $id)->first();
            if (!$findProductCarousel) {
                return $this->sendError('Product Carousel not found', [], 404);
            }
            DB::table('products_carousel')->where('id', $id)->update([
                'status' => $request->status
            ]);
            return $this->sendResponse('Product Carousel updated successfully', 'Product Carousel updated successfully');
        }catch(Exception $e){
            return $this->sendError('Something went wrong', [], 500);
        }
    }

    // deleteProductCarousel 
    public function deleteProductCarousel($id){
        try{
            $findProductCarousel = DB::table('products_carousel')->where('id', $id)->first();
            if (!$findProductCarousel) {
                return $this->sendError('Product Carousel not found', [], 404);
            }
            // delete src file from s3
           if($findProductCarousel->imageSrc){
            S3Service::deleteFile($findProductCarousel->imageSrc);
           }
            DB::table('products_carousel')->where('id', $id)->delete();
            return $this->sendResponse('Product Carousel deleted successfully', 'Product Carousel deleted successfully');
        }catch(Exception $e){
            return $this->sendError('Something went wrong', [], 500);
        }
    }
    // getAllProductCarousel 
    public function getAllProductCarousel(){
        try{
            $productCarousel = DB::table('products_carousel')
            ->leftJoin('products', 'products_carousel.productId', '=', 'products.id')
            ->select(
                'products_carousel.*',
                'products.title as productName',
                'products.id as productId'

            )->get();

            $modifiedProductCarousel = $productCarousel->map(function ($item) {
                return [
                    'id' => (string) $item->id,
                    'productId' => (string) $item->productId,
                    'productName' =>  $item->productName,
                    'imageSrc' =>  $item->imageSrc,
                    'status' =>  $item->status,
                    'date' => $item->created_at
                ];
            });
            return $this->sendResponse($modifiedProductCarousel, 'Product Carousel fetched successfully');
        }catch(Exception $e){
            return $this->sendError($e->getMessage(), [], 500);
        }
    }
}
