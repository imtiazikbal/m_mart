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

       
        
        $settings = DB::table('settings')->first();

        if ($request->has('logo') || $request->has('favicon')) {

            // Handle logo upload if present
            if ($request->hasFile('logo')) {
                $settingsLogo = $request->file('logo');
                $settingsLogoPath = 'settings/logo'; // Example path, adjust as needed
                
                // Upload new logo and delete the old one if it exists
                $settingsLogoUrl = S3Service::uploadSingle($settingsLogo, $settingsLogoPath);
                
            }
        
            // Handle favicon upload if present
            if ($request->hasFile('favicon')) {
                $settingsFavicon = $request->file('favicon');
                $settingsFaviconPath = 'settings/favicon';
        
                // Upload new favicon and delete the old one if it exists
                $settingsFaviconUrl = S3Service::uploadSingle($settingsFavicon, $settingsFaviconPath);
               
                
                // Update the settings favicon URL in the database or config
            }
        
        }
        

            if ($settings) {
            DB::table('settings')->update([
                'logo' => $settingsLogoUrl ?? $settings->logo,
                'favicon' => $settingsFaviconUrl ?? $settings->favicon,
                'title' => $request->title ?? $settings->title,
                'updated_at' => now(),

            ]);

            // update admins password 
            if($request->has('newPassword') && $request->has('currentPassword') && $request->has('confirmPassword')) {

            $findAminds = DB::table('admins')->where('email', $request->email)->first();

            if(!Hash::check($request->currentPassword, $findAminds->password)) {
                return $this->sendError('Password does not match', [], 422);
            }

            if($findAminds && !Hash::check($request->currentPassword, $findAminds->password)) {
                return $this->sendError('Invalid current password', [], 422);
            }
            // Update the password
            DB::table('admins')->where('email', $request->email)->update([
                'password' => Hash::make($request->newPassword)
            ]);
                
            }
        } else {
            DB::table('settings')->insert([
                'logo' => $settingsLogoUrl ?? null,
                'favicon' => $settingsFaviconUrl ?? null,
                'title' => $request->title ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return $this->sendResponse('Setting created successfully', 'Setting created successfully.');
        }
    

        // getSetting 
        public function getSetting(Request $request)
        {
           
            $adminId =  $request->headers->get('userID');
            $admin = DB::table('admins')->where('id', $adminId)->first();
            $settings = DB::table('settings')->first();
            if($settings) {
                $response = [
                    'logo' => $settings->logo,
                    'favicon' => $settings->favicon,
                    'title' => $settings->title,
                    'email' => $admin->email
                ];
                return $this->sendResponse($response, 'Setting retrieved successfully.');
            }else{
                $response = [
                    'logo' => null,
                    'favicon' => null,
                    'title' => null,
                    'email' => $admin->email
                ];
                return $this->sendResponse($response, 'Setting retrieved successfully.');
            }
           
        }

    }

