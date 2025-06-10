<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class SubscribeController extends ResponseController
{
    //subscribe
    public function subscribe(Request $request)
    {
       try{
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors(), 400);
        }

        $findSubscriber = DB::table('subscribes')->where('email', $request->email)->first();
        if($findSubscriber){
            return $this->sendError('You are already subscribed', [], 404);
        }
        $subscribe = DB::table('subscribes')->insert([
            'email' => $request->email,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $this->sendResponse('Subscribe successfully', 'Subscribe successfully');
       }catch(Exception $e){
           return $this->sendError('', $e->getMessage());
       }
    }

    // getAllSubscribers 
    public function getAllSubscribers(){
        try{
            $subscribers = DB::table('subscribes')->get();
        $subscribersModified = $subscribers->map(function ($subscriber) {
            return [
                'id' => (string)$subscriber->id,
                'email' => $subscriber->email,
                'date' => $subscriber->created_at
            ];
        });
        return $this->sendResponse($subscribersModified, 'Subscribers successfully');
        }catch(Exception $e){
            return $this->sendError('', $e->getMessage());
        }
    }

    // deleteSubscribe 
    public function deleteSubscribe($id){
        try{
            $findSubscriber = DB::table('subscribes')->where('id', $id)->first();
        if(!$findSubscriber){
            return $this->sendError('Subscribe not found', [], 404);
        }
        $subscribe = DB::table('subscribes')->where('id', $id)->delete();
        return $this->sendResponse('Subscribe deleted successfully','Subscribe deleted successfully');
        }catch(Exception $e){
            return $this->sendError('', $e->getMessage());
        }
    }


}
