<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class RefundPrivacyController extends ResponseController
{
    public function storeRefundPolicy(Request $request)
    {
      try{
        $validator = Validator::make($request->all(), [
            'text' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $terms = DB::table('refund_policies')->first();
        if ($terms) {
            $terms->update([
                'text' => $request->text,
            ]);
        } else {
            DB::table('refund_policies')->insert([
                'text' => $request->text,
            ]);
        }
        return $this->sendResponse('Refund Policy created successfully', 'Refund Policy created successfully.');
      }catch(Exception $ex){
        return $this->sendError($ex->getMessage());
      }
    }
    // get all refund policy 
    public function getRefundPolicy()
    {
        try{
            $terms = DB::table('refund_policies')->select('text')->first();
        return $this->sendResponse($terms, 'Refund Policy fetched successfully.');
        }catch(Exception $ex){
            return $this->sendError($ex->getMessage());
        }
    }
}
