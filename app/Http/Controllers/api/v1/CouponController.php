<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CouponController extends ResponseController
{

// getCoupon 
    public function getCoupon(){
        $coupons = DB::table('coupons')->get();

        $couponsModified = $coupons->map(function ($coupon) {
            return [
                'id' => (string) $coupon->id,
                'couponCode' => $coupon->couponCode,
                'minPrice' => $coupon->minPrice,
                'discountPercent' => $coupon->discountPercent,
                'status' => $coupon->status,
            ];
        });
        return $this->sendResponse($couponsModified, 'Coupons fetched successfully.');
    }


    // getCustomerCoupon  
    public function getCustomerCoupon(){
        $coupons = DB::table('coupons')->where('status', 'active')->get();

        $couponsModified = $coupons->map(function ($coupon) {
            return [
                'couponCode' => $coupon->couponCode,
                'minPrice' => (int) $coupon->minPrice,
                'discountPercent' => (int)  $coupon->discountPercent,
            ];
        });
        return $this->sendResponse($couponsModified, 'Coupons fetched successfully.');
    }


    //storeCoupon 
    public function storeCoupon(Request $request)
    {
        




        $validator = Validator::make($request->all(), [
            'couponCode' => 'required',
            'discountPercent' => 'required',
        ]);
        
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
        }

        // find couponCode are allready exist
        $findCoupon = DB::table('coupons')->where('couponCode', $request->couponCode)->first();
        if ($findCoupon) {
            return $this->sendError('Coupon Code are allready exist', [], 422);
        }

        // Insert coupon
        DB::table('coupons')->insert([
            'couponCode' => $request->couponCode,
            'minPrice' => $request->minPrice,
            'discountPercent' => $request->discountPercent,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->sendResponse('Coupon created successfully', 'Coupon created successfully.');
    }


    // updateCoupon 
    public function updateCoupon(Request $request, $id){

        $validator = Validator::make($request->all(), [
            'couponCode' => 'nullable',
            'discountPercent' => 'nullable',
        ]);
        
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
        }

        // find couponCode are allready exist
        $findCoupon = DB::table('coupons')->where('id', $id)->first();
        if (!$findCoupon) {
            return $this->sendError('Coupon not found', [], 422);
        }

        // Update coupon code 
        DB::table('coupons')->where('id', $id)->update([
            'couponCode' => $request->couponCode ?? $findCoupon->couponCode,
            'minPrice' => $request->minPrice ?? $findCoupon->minPrice,
            'discountPercent' => $request->discountPercent ?? $findCoupon->discountPercent,
            'status' => $request->status ?? $findCoupon->status,
            'updated_at' => now(),
        ]);

        return $this->sendResponse('Coupon updated successfully', 'Coupon updated successfully.');
    }

    // deleteCoupon 
    public function deleteCoupon($id){

        $findCoupon = DB::table('coupons')->where('id', $id)->first();
        if (!$findCoupon) {
            return $this->sendError('Coupon not found', [], 422);
        }
        DB::table('coupons')->where('id', $id)->delete();
        return $this->sendResponse('Coupon deleted successfully', 'Coupon deleted successfully.');
    }

// statusUpdate 
    public function statusUpdate(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'status' => 'required',
        ]);
        
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
        }
        $findCoupon = DB::table('coupons')->where('id', $id)->first();
        if (!$findCoupon) {
            return $this->sendError('Coupon not found', [], 422);
        }
        DB::table('coupons')->where('id', $id)->update([
            'status' => $request->status,
        ]);
        return $this->sendResponse('Coupon status successfully update', 'Coupon status successfully update');
    }

   
}
