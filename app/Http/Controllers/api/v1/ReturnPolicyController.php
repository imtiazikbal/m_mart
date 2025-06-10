<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ReturnPolicyController extends ResponseController
{
    // storeTermsAndCondition if terms and condition exists then update
    public function storeReturnPolicy(Request $request)
    {
      try{
        $validator = Validator::make($request->all(), [
            'text' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $terms = DB::table('return_policies')->first();
        if ($terms) {
          DB::table('return_policies')->update([
                'text' => $request->text,
                'updated_at' => now()
            ]);
        } else {
            DB::table('return_policies')->insert([
                'text' => $request->text,
                'updated_at' => now(),
                'created_at' => now()
            ]);
        }
        return $this->sendResponse('Return Policy created successfully', 'Return Policy created successfully.');
      }catch(Exception $ex){
        return $this->sendError($ex->getMessage());
      }
    }
    // getAllTermsAndCondition 
    public function getReturnPolicy()
    {
        try{
            $terms = DB::table('return_policies')->select('text')->first();
        return $this->sendResponse($terms, 'Return Policy fetched successfully.');
        }catch(Exception $ex){
            return $this->sendError($ex->getMessage());
        }
    }
}
