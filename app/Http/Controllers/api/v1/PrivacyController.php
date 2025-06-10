<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PrivacyController extends ResponseController
{
    public function storePrivacyPolicy(Request $request)
    {
      try{
        $validator = Validator::make($request->all(), [
            'text' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $terms = DB::table('privacy_policies')->first();
        if ($terms) {
            $terms->update([
                'text' => $request->text,
                'updated_at' => now()
            ]);
        } else {
            DB::table('privacy_policies')->insert([
                'text' => $request->text,
                'updated_at'=> now(),
                'created_at' => now()
            ]);
        }
        return $this->sendResponse('Privacy Policy created successfully', 'Privacy Policycreated successfully.');
      }catch(Exception $ex){
        return $this->sendError($ex->getMessage());
      }
    }
    // Privacy Policy 
    public function getAllPrivacyPolicy()
    {
        try{
            $terms = DB::table('privacy_policies')->select('text')->first();
        return $this->sendResponse($terms, 'Privacy Policy fetched successfully.');
        }catch(Exception $ex){
            return $this->sendError($ex->getMessage());
        }
    }
}
