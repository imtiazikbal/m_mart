<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class RefundCencellationController extends ResponseController
{
    public function storeRefundCencellation(Request $request)
    {
      try{
        $validator = Validator::make($request->all(), [
            'text' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $terms = DB::table('refund_cancellations')->first();
        if ($terms) {
            $terms->update([
                'text' => $request->text,
            ]);
        } else {
            DB::table('refund_cancellations')->insert([
                'text' => $request->text,
            ]);
        }
        return $this->sendResponse('Refund Cencellation created successfully', 'Refund Cencellation created successfully.');
      }catch(Exception $ex){
        return $this->sendError($ex->getMessage());
      }
    }
    // get refund cencellation 
    public function getRefundCencellation()
    {
        try{
            $terms = DB::table('refund_cancellations')->select('text')->first();
        return $this->sendResponse($terms, 'Refund Cencellation fetched successfully.');
        }catch(Exception $ex){
            return $this->sendError($ex->getMessage());
        }
    }
}
