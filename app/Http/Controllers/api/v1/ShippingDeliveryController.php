<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ShippingDeliveryController extends ResponseController
{
    public function storeShippingDelivery(Request $request)
    {
      try{
        $validator = Validator::make($request->all(), [
            'text' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $terms = DB::table('shipping_deliveries')->first();
        if ($terms) {
            $terms->update([
                'text' => $request->text,
            ]);
        } else {
            DB::table('shipping_deliveries')->insert([
                'text' => $request->text,
            ]);
        }
        return $this->sendResponse('Shipping Delivery created successfully', 'Shipping Delivery created successfully.');
      }catch(Exception $ex){
        return $this->sendError($ex->getMessage());
      }
    }
    // getAllTermsAndCondition 
    public function getShippingDelivery()
    {
        try{
            $terms = DB::table('shipping_deliveries')->select('text')->first();
        return $this->sendResponse($terms, 'Shipping Delivery fetched successfully.');
        }catch(Exception $ex){
            return $this->sendError($ex->getMessage());
        }
    }
}
