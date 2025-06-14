<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Helper\JWTToken;
use App\Mail\MailSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends ResponseController
{
    public function login(Request $request) {
       try{
         
    
        $phone = $request->phone;
        $concateEmail = $phone . '@gmail.com';

        $user = DB::table('users')->where('email', $concateEmail)->first();

   

      if ($user) {
        // Token generation
        $token = JWTToken::createToken($user->email, $user->id);
    
         return $this->sendResponse([
                'token' => $token,
            ], 'Login successfully');
       }

        $otp = random_int(100000, 999999); // Secure OTP generation
    
        $newuser = User::updateOrCreate(
            ['phone' => $phone],
            [
                'email' => $phone . '@gmail.com',
                'otp' => $otp,
                'otp_expiry' => now()->addMinutes(10),
                'otp_sent_at' => now(),
            ]
        );
    
        // Send OTP to user's email
//Mail::to($email)->send(new MailSender('OTP', $otp, $user->email));
    
        // Token generation
        $token = JWTToken::createToken($newuser->email, $newuser->id);
    
         return $this->sendResponse([
                'token' => $token,
            ], 'OTP verified successfully');
       }catch(Exception $e){
        return $this->sendError('', $e->getMessage());
       }
    }
    

     
        public function tokenVarification(Request $request) {
            try{

            $userId = $request->headers->get('userID');
            $userData =  DB::table('users')->where('id', $userId)->first();

            if (!$userData) {
                return $this->sendError('User not found', [], 404);
            }

            $user =  DB::table('users')->where('id', $userId)->first();
            return $this->sendResponse($user, 'User Email and ID retrieved successfully.');
            }catch(Exception $e){
                return $this->sendError('', $e->getMessage(),0);
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
            $user = User::where('email', $validator->validated()['email'])->first();
    
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
            $user->update([
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


        // Update user name by token
        public function updateUsername(Request $request) {

           try{
            $validator = Validator::make($request->all(), [
                'firstName' => 'required|string|max:255',
                'lastName' => 'required|string|max:255',
            ]);
    
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            $userId = $request->headers->get('userID');
            $user = DB::table('users')->where('id', $userId)->first();
            if (!$user) {
                return $this->sendError('User not found', [], 404);
            }
            $user = DB::table('users')->where('id', $userId)->update([
                'firstName' => $request->firstName ?? $user->firstName,
                'lastName' => $request->lastName ?? $user->lastName,
            ]);
            return $this->sendResponse('User name updated successfully', 'User name updated successfully.');
           }catch(Exception $e){
            return $this->sendError('', $e->getMessage(),0);
           }
        }

        // getProfile 
        public function getProfile(Request $request) {
           try{
             // Retrieve userId from headers or respond with an error if missing
             $userId = $request->headers->get('userID');
 // Fetch user
        $user = DB::table('users')->where('id', $userId)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'data' => null
            ]);
        }
        if(!$user){
            return $this->sendError('User not found', [], 404);
        }
         
                    // Fetch user invoices
        $orders = DB::table('invoices')
            ->where('userId', $userId)
            ->orderByDesc('created_at')
            ->get();

        $orderDetails = [];

        foreach ($orders as $order) {
            // Fetch invoice products
            $items = DB::table('invoice_products')
                ->join('products', 'invoice_products.productId', '=', 'products.id')
                ->where('invoice_products.invoiceId', $order->id)
                ->select(
                    'products.title as name',
                    'invoice_products.size as variant'
                )
                ->get();

            $orderDetails[] = [
                'id' => 'MMBD' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                'date' => date('Y-m-d', strtotime($order->created_at)),
                'status' => $order->status,
                'total' => (float) $order->payable,
                'items' => $items
            ];
        }

        // Build response
        $response = [
            'name' => trim($user->firstName . ' ' . $user->lastName),
            'email' => $user->email,
            'phone' => $user->phone,
            'orders' => $orderDetails
        ];

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
           }catch(Exception $e){
            return $this->sendError('Error retrieving user profile', $e->getMessage(),500);
           }
        }

        // get all users
        public function getAllUsers(Request $request) {
            try{
                $users = DB::table('users')->get();
                $modifiedUser = $users->map(function ($user) {
                    return [
                        'id' => (string) $user->id,
                        'email' => $user->email,
                        'name' => $user->firstName . ' ' . $user->lastName,
                        'status' => $user->status,
                        'date' => $user->created_at,

                    ];
                
                });

                return $this->sendResponse($modifiedUser, 'Users retrieved successfully.');
            }catch(Exception $e){
                return $this->sendError('Error retrieving users', $e->getMessage(),500);
            }
        }


        // updateUserStatus 
        public function updateUserStatus(Request $request, $id) {
            try{
                $validator = Validator::make($request->all(), [
                    'status' => 'required|in:Active,Inactive',
                ]);
                if ($validator->fails()) {
                    return $this->sendError('Validation Error', $validator->errors(), 422);
                }
                
                $user = DB::table('users')->where('id', $id)->first();
                if (!$user) {
                    return $this->sendError('User not found', [], 404);
                }
                $user = DB::table('users')->where('id', $id)->update([
                    'status' => $request->status ?? $user->status,
                ]);
                return $this->sendResponse('User status updated successfully', 'User status updated successfully.');
            }catch(Exception $e){
                return $this->sendError('Error updating user status', $e->getMessage(),500);
            }
        }
        
    
    
}