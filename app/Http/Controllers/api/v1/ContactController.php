<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ContactController extends ResponseController
{
    //contactUs function
    public function contactUs(Request $request){
        
      try{
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
            'subject' => 'required',
            'message' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
        }
        $contactUs = DB::table('contacts')->insert([
            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $this->sendResponse('Contact Store successfully', 'Contact Us successfully');
      }catch(Exception $e){
        return $this->sendError('', $e->getMessage());
      }
    }

    // getAllContact 
    public function getAllContact(){
        try{
            $contactUs = DB::table('contacts')->get();
        $contactUsModified = $contactUs->map(function ($contact) {
            return [
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'subject' => $contact->subject,
                'message' => $contact->message,
                'date' => $contact->created_at
            ];
        });
        return $this->sendResponse($contactUsModified, 'Contact Us successfully');
        }catch(Exception $e){
            return $this->sendError('', $e->getMessage());
        }
    }

    // deleteContact
    public function deleteContact($id){
        try{
            $findContact = DB::table('contacts')->where('id', $id)->first();

        if (!$findContact) {
            return $this->sendError('Contact Us not found', [], 404);
        }

        $contactUs = DB::table('contacts')->where('id', $id)->delete();
        return $this->sendResponse('Contact Us deleted successfully', 'Contact Us deleted successfully');

        }catch(Exception $e){
            return $this->sendError('', $e->getMessage());
        }
    }
}
