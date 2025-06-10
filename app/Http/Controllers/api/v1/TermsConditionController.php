<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TermsConditionController extends ResponseController
{
    // storeTermsAndCondition if terms and condition exists then update
    public function storeTermsAndCondition(Request $request)
    {
      try{
        $validator = Validator::make($request->all(), [
            'text' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $terms = DB::table('terms_conditions')->first();
        if ($terms) {
          DB::table('terms_conditions')->update([
                'text' => $request->text,
                'updated_at' => now()
            ]);
        } else {
            DB::table('terms_conditions')->insert([
                'text' => $request->text,
                'updated_at' => now(),
                'created_at' => now()
            ]);
        }
        return $this->sendResponse('Terms and Condition created successfully', 'Terms and Condition created successfully.');
      }catch(Exception $ex){
        return $this->sendError($ex->getMessage());
      }
    }
    // getAllTermsAndCondition 
    public function getAllTermsAndCondition()
    {
        try{
            $terms = DB::table('terms_conditions')->select('text')->first();
        return $this->sendResponse($terms, 'Terms and Condition fetched successfully.');
        }catch(Exception $ex){
            return $this->sendError($ex->getMessage());
        }
    }
    
}
