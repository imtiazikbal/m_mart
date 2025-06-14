<?php

namespace App\Http\Controllers\api\v1;

use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class SettingsController extends ResponseController
{
    //storeSetting
    public function storeSetting(Request $request)
    {

       
       
            // update admins password 
            if($request->has('newPassword') && $request->has('oldPassword')) {

            $findAminds = DB::table('admins')->where('email', $request->email)->first();

            if(!Hash::check($request->oldPassword, $findAminds->password)) {
                return $this->sendError('Password does not match', [], 422);
            }

            if($findAminds && !Hash::check($request->oldPassword, $findAminds->password)) {
                return $this->sendError('Invalid current password', [], 422);
            }
            // Update the password
            DB::table('admins')->where('email', $request->email)->update([
                'password' => Hash::make($request->newPassword)
            ]);
                
            }
        return $this->sendResponse('Setting created successfully', 'Setting created successfully.');
        }
    

        // getSetting 
        public function getSetting(Request $request)
        {
           
            $adminId =  $request->headers->get('userID');
            $admin = DB::table(table: 'admins')->where('id', $adminId)->first();
            $settings = DB::table('settings')->first();
            if($settings) {
                $response = [
                   
                    'email' => $admin->email
                ];
                return $this->sendResponse($response, 'Setting retrieved successfully.');
            }else{
                $response = [
                 
                    'email' => $admin->email
                ];
                return $this->sendResponse($response, 'Setting retrieved successfully.');
            }
           
        }

    }

