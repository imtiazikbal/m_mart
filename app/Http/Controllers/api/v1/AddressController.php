<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AddressController extends ResponseController
{

    //getAddress

    public function getAddress(Request $request)
    {
       try{
        $userId = $request->headers->get('userID');
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return $this->sendError('User not found', [], 404);
        }
        $address = DB::table('addresses')->where('user_id', $userId)->get();
        $modifiedAddress = $address->map(function ($address) {
            return [
                'id' => $address->id,
                'firstName' => $address->firstName,
                'lastName' => $address->lastName,
                'country' => $address->country,
                'city' => $address->city,
                'postCode'=> $address->postCode,
                'address' => $address->address,
                'phone' => $address->phone,
                'isDefault' => $address->isDefault,
            ];
        });
        return $this->sendResponse($modifiedAddress, 'Address retrieved successfully.');
       }catch (Exception $e){
        return $this->sendError('Error retrieving address', [], 500);
       }
    }
    // storeAddress

    public function storeAddress(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'firstName' => 'required',
                'lastName' => 'required',
                'country' => 'required',
                'city' => 'required',
                'address' => 'required',
                'phone' => 'required',
                'postCode' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation error', $validator->errors());
            }
            $userId = $request->headers->get('userID');
            $user = DB::table('users')->where('id', $userId)->first();
            if (!$user) {
                return $this->sendError('User not found', [], 404);
            }
    
            $address = DB::table('addresses')->insert([
                'user_id' => $userId,
                'firstName' => $request->firstName,
                'lastName' => $request->lastName,
                'country' => $request->country,
                'city' => $request->city,
                'address' => $request->address,
                'phone' => $request->phone,
                'postCode' => $request->postCode,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return $this->sendResponse('Address created successfully', 'Address created successfully.');
        }catch (Exception $e){
            return $this->sendError('Error creating address', [], 500);
        }
    }


    // updateAddress 

    public function updateAddress(Request $request)
    {
        try{
            $userId = $request->headers->get('userID');
            $user = DB::table('users')->where('id', $userId)->first();
            
            if (!$user) {
                return $this->sendError('User not found', [], 404);
            }
        
            $userAddress = DB::table('addresses')->where('user_id', $userId)->where('id', $request->id)->first();
        
            if (!$userAddress) {
                return $this->sendError('Address not found', [], 404);
            }
        
            DB::table('addresses')->where('user_id', $userId)->where('id', $request->id)->update([
                'firstName' => $request->firstName ?? $userAddress->firstName,
                'lastName' => $request->lastName ?? $userAddress->lastName,
                'country' => $request->country ?? $userAddress->country,
    
                'city' => $request->city ?? $userAddress->city,
                'address' => $request->address ?? $userAddress->address,
                'phone' => $request->phone ?? $userAddress->phone,
                'postCode' => $request->postCode ?? $userAddress->postCode,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        
            return $this->sendResponse('Address updated successfully', 'Address updated successfully.');
        }catch (Exception $e){
            return $this->sendError('Error updating address', [], 500);
        }
    }

    // deleteAddress
    public function deleteAddress(Request $request)
    {
       try{
        $userId = $request->headers->get('userID');
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return $this->sendError('User not found', [], 404);
        }

        $addressFind = DB::table('addresses')->where('user_id', $userId)->where('id', $request->id)->first();
        if (!$addressFind) {
            return $this->sendError('Address not found', [], 404);
        }
        DB::table('addresses')->where('user_id', $userId)->where('id', $request->id)->delete();
        return $this->sendResponse('Address deleted successfully', 'Address deleted successfully.');
       }catch (Exception $e){
        return $this->sendError('Error deleting address', [], 500);
       }
    }
    
}
