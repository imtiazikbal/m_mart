<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use Carbon\Carbon;
use App\Helper\JWTToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends ResponseController
{
    public function signup(Request $request)
    {

        try{
//dd($request->all());

$validator = Validator::make($request->all(), [
    'email' => 'required|email',
    'password' => 'required']);

if ($validator->fails()) {
    return $validator->errors();
}
$user = DB::table('admins')->where('email', $request->email)->first();
if ($user) {
   return $this->sendError('Email already exists');
} else {
    $input = $request->all();
    $input['created_at'] = now();
$input['updated_at'] = now();
    $input['password'] = bcrypt($input['password']);
    $user =DB::table('admins')->insert($input);

    if ($user) {
        return $this->sendResponse($user, 'Admin created successfully.');
    }
}
        }catch(Exception $e){
            return redirect()->back()->with("error", $e->getMessage());
        }
    }


    public function login(Request $request)
    {
      try{
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);
    
        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors());
        }
    
        // Attempt to retrieve the user
        $user = DB::table('admins')->where('email', $request->email)->first();
        if(!$user){
            return $this->sendError('User not found');
        }
 
        // Check if user exists and validate password
        if (!Hash::check($request->password, $user->password)) {
            return $this->sendError('Invalid credentials');
        }
    
        // Token generation
        $token = JWTToken::createToken($user->email, $user->id);
        $success['token'] = $token;
     
    
        return $this->sendResponse($success, 'Admin logged in successfully.');
       
      }catch(Exception $e){
        
        return redirect()->back()->with("error", $e->getMessage());
      }
    }




// admin user get 
public function tokenVarificationForAdmin(Request $request){

   try{
    $userId = $request->headers->get('userID');
    $adminData =  DB::table('admins')->where('id', $userId)->first();

    if (!$adminData) {
        return $this->sendError('Admin not found', [], 404);
    }
    $admin = DB::table('admins')->where('id', $userId)->select('id', 'email')->first();
    return $this->sendResponse($admin, 'User Email and ID retrieved successfully.');
   }catch(Exception $e){
    return redirect()->back()->with("error", $e->getMessage());
   }

}

public function verifyOtp(Request $request)
        {
            
            try{
            // Validate the input for OTP and email
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'otp' => 'required|digits:6',
            ]);
    
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
    
            // Find the user by email
            $user = DB::table('admins')->where('email', $validator->validated()['email'])->first();
    
            // Check if user exists
            if (!$user) {
                return $this->sendError('User not found', [], 404);
            }
    
            // Check if the OTP matches and is not expired
            if ($user->otp !== $request->otp) {
                return $this->sendError('Invalid OTP', [], 400); // Incorrect OTP
            }
    
            // Check if OTP is expired
            if (Carbon::parse($user->otp_expiry)->isPast()) {
                return $this->sendError('OTP expired, please request a new one.', [], 400); // OTP Expired
            }
    
            // OTP is valid, issue token and reset OTP fields
            $token = JWTToken::createToken($user->email, $user->id);
    
            // Reset OTP after successful verification to prevent reuse
            DB::table('admins')->where('id', $user->id)->update([
                'otp' => null,
                'otp_expiry' => null,
                'otp_sent_at' => null
            ]);
    
            return $this->sendResponse([
                'token' => $token,
            ], 'OTP verified successfully');
                }catch(Exception $e){
                    return $this->sendError('', $e->getMessage(),0);
                }
    
        }

        // varifyToken
        public function varifyToken(Request $request){
               $token = $request->token;

        if (!$token) {
            return $this->sendError([], 'Token is required', 400);
        }

        $data = JWTToken::adminTokenVarification($token);

        if ($data === 'unauthorized') {
 $response = [
            'isAuthorized' => "0"
        
    ];
    return $this->sendResponse($response, 'Admin logged in successfully.');
            
}



        $admin = DB::table('admins')->where('id', $data->userID)->first();
if ($admin) {
    $response = [
        
            'isAuthorized' => "1"
        
    ];
    return $this->sendResponse($response, 'Admin logged in successfully.');
} else {
    $response = [
            'isAuthorized' => "0"
        
    ];
    return $this->sendResponse($response, 'Admin logged in successfully.');}
            
        }
}