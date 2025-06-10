<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use Stripe\StripeClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\StripePayment;

class StripeControllerController extends ResponseController
{
    
    public function storePayment(Request $request)
    {
       
        try{


            DB::beginTransaction(); // Start the transaction

        // Fetch Stripe API Key
       // $stripe_credentials = DB::table('stripe_credentials')->first();
      // $stripe = new StripeClient($stripe_credentials->apiSecret);

        $userId = $request->headers->get('userID');
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return $this->sendError('User not found', [], 404);
        }

       

        $requestProductIds = [];

        // Collect product IDs from the request
        foreach ($request->cartItems as $item) {
            $requestProductIds[] = $item['productId'];
        }
        
        // Find products in the database
        $products = DB::table('products')->whereIn('id', $requestProductIds)->pluck('id')->toArray();
        
        // Check if any product IDs from the request are missing in the database
        $missingProductIds = array_diff($requestProductIds, $products);
        
        if (!empty($missingProductIds)) {
            return $this->sendError('Product not found with these IDs: ' . implode(', ', $missingProductIds), [], 404);
        }
        
        // Proceed with your logic if all products are found
        
        

        // Prepare an array to store the data for batch insertion
        $cartData = [];
        // Loop through each cart item and prepare data for insertion
        foreach ($request->cartItems as $item) {
            $cartData[] = [
                'userId' => $userId,
                'productId' => $item['productId'],
                'quantity' => $item['quantity'],
                'discountPercent' => $item['discountPercent'] ?? 0, // Default to 0 if not provided
                'price' => $item['price'],
                'created_at' => now(), // Add timestamps if necessary
                'updated_at' => now(),
            ];
        }
        
        // Insert all items in a single query
        DB::table('product_carts')->insert($cartData);

        // products check 
        
        // Prepare the delivery data
        $deliveryData = [
            'userId' => $request->userId,
            'firstName' => $request->firstName,
            'lastName' => $request->lastName,
            'email' => $request->email,
            'phone' => $request->phone,
            'country' => $request->country,
            'city' => $request->city,
            'address' => $request->address,
            'postCode' => $request->postCode,
        ];
        // User cart item get price quantity and discount

        
        
        // Initialize total price variable
        $totalPrice = 0; // Initialize total price

        // Fetch items from the cart for the specific user
        $cartItems = DB::table('product_carts')->where('userId', $userId)->get();
        
        foreach ($cartItems as $item) {
            
            // Calculate the discount amount if applicable
            $discountAmount = ($item->discountPercent ?? 0) > 0 ? 
                ($item->price * ($item->discountPercent / 100)) : 0; // Calculate discount amount
        
            // Calculate the price after discount
            $priceAfterDiscount = $item->price - $discountAmount; // Price after discount

            $originalPrice = $item->price * $item->quantity;

            // Calculate total price for the current item
            $totalItemPrice = $priceAfterDiscount * $item->quantity; // Total price for the item
            // Add the item total to the overall total price
            $totalPrice += $totalItemPrice; // Update total price
        }


// Apply coupon if it exists in the request and exits in database 
if ($request->has('coupon') && DB::table('coupons')->where('couponCode', $request->coupon)->exists()) {
    $coupon = DB::table('coupons')->where('couponCode', $request->coupon)->first();
    // Ensure the coupon has a discountPercent field and apply it
    $couponDiscountPercent = $coupon->discountPercent ?? 0;
    if ($couponDiscountPercent > 0) {
        $discountAmount = $totalPrice * ($couponDiscountPercent / 100);
        // Subtract the calculated discount from the total price
        $totalPrice = max(0, $totalPrice - $discountAmount);  
    }
}

// Return the calculated total price




        $tranId = 'TRX_' . strtoupper(uniqid('TRX' . time() . '_', true));

        // Insert invoice record and retrieve the invoice ID
        $invoiceId = DB::table('invoices')->insertGetId([
            'total' => $originalPrice,
            'vat' => 0,
            'payable' => $totalPrice,
            'cusDetails' => json_encode($deliveryData),
            'tranId' => $tranId,
            'paymentStatus' => 'Pending',
            'userId' => $userId,
            'paymentMethod' => 'stripe',
            'status' => 'Pending',
            'coupon' => $request->coupon ?? null,
            'created_at' => now(),
            'updated_at'=> now(),
        ]);

        // store Delivery address data in the delivery_addresses table
        DB::table('delivery_addresses')->insert([
            'invoiceId' => $invoiceId,
            'firstName' => $request->firstName,
            'lastName' => $request->lastName,
            'email' => $request->email,
            'phone' => $request->phone,
            'country' => $request->country,
            'city' => $request->city,
            'address' => $request->address,
            'postCode' => $request->postCode,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert invoice products
        foreach ($cartData as $product) {
            DB::table('invoice_products')->insert([
                'invoiceId' => $invoiceId,
                'productId' => $product['productId'], // Use 'productId' correctly
                'userId' => $userId, // Use 'user_id' to match your schema
                'salePrice' => $product['price'], // Use 'price' directly from cartData
                'quantity' => $product['quantity'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
          // Initialize an array to hold the product IDs
        $productIds = [];

        // Step 1: Collect all product IDs from the cart items
        foreach ($request->cartItems as $item) {
            $productIds[] = $item['productId']; // Collecting product IDs
        }

        // Step 2: Fetch product names using a single query
        $productNames = DB::table('products')
            ->whereIn('id', $productIds) // Use whereIn for efficient fetching
            ->pluck('title'); // Assuming 'title' is the column name for product names

// Step 3: Convert the product names into a comma-separated string
//$allProductNames = implode(', ', $productNames);

       // Create a Stripe Checkout session
        // $response = $stripe->checkout->sessions->create([
        //     'line_items' => [[
        //         'price_data' => [
        //             'currency' => 'usd',
        //             'product_data' => [
        //                 'name' => $productNames,
        //             ],
        //             'unit_amount' => (int)($totalPrice * 100),
        //         ],
        //         'quantity' => 1,
        //     ]],
        //     'mode' => 'payment',
        //     'success_url' => route('stripe.success') . '?tranId=' . $tranId . '&session_id={CHECKOUT_SESSION_ID}',
        //     'cancel_url' => route('stripe.cancel', ['tranId' => $tranId]),
        // ]);

        // // Redirect to the Stripe Checkout page
        // if (isset($response->id)) {
        //     return $this->sendResponse($response,'Payment method created successfully');
        // } else {
        //     return $this->sendError('Error creating payment method', [], 500);
        //     }

        // delerte all product from cart
        DB::table('product_carts')->where('userId', $userId)->delete();
           // Commit the transaction
           DB::commit();


             return $this->sendResponse('Payment method created successfully', 'Payment method created successfully');
        }catch(Exception $e){
             // If an error occurs, roll back the transaction
        DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message'=> $e->getMessage()
                ], 500);
        }
    }

    
        public function success(Request $request)
        {

            try{
            $stripe_credentials = DB::table('stripe_credentials')->first();
            if(isset($request->session_id)) {
    
                $stripe = new StripeClient($stripe_credentials->apiKey);
                $response = $stripe->checkout->sessions->retrieve($request->session_id);
                $tranId = $request->query('tranId');
               StripePayment::stripePaymentSuccess($tranId);
              
                DB::table('stripe_payment_success_responces')->insert([
                    'checkoutSessionId' => $response->id,
                    'currency' => $response->currency,
                    'amountSubtotal' => $response->amount_subtotal,
                    'amountTotal' => $response->amount_total,
                    'createdAt' => Carbon::createFromTimestamp($response->created),
                    'expiresAt' => Carbon::createFromTimestamp($response->expires_at),
                    'paymentIntent' => $response->payment_intent,
                    'paymentStatus' => $response->payment_status,
                    'customerName' => $response->customer_details->name ?? null,
                    'customerEmail' => $response->customer_details->email ?? null,
                    'customerCity' => $response->customer_details->address->city ?? null,
                    'customerCountry' => $response->customer_details->address->country ?? null,
                    'customerLine1' => $response->customer_details->address->line1 ?? null,
                    'customerPostal_code' => $response->customer_details->address->postal_code ?? null,
                ]);


            // $productIds = $productsList->pluck('product_id')->toArray();

            // // Fetch the full products based on product IDs, specifically selecting fullProduct field
            // $products = Product::whereIn('id', $productIds)
            //     ->pluck('fullProduct')
            //     ->toArray();

               
        
            // // Manually build the URLs parameter
            // $urls = '[' . implode(',', array_map(function ($url) {
            //     return '"' . $url . '"';
            // }, $products)) . ']';
           
        
            // Manually build the full redirect URL
           // $redirectUrl = 'https://gloomlash.com/payment?status="Success"&urls='.$urls;

           $redirectUrl = 'https://elvankitchen.com/payment?status=Success';

           return redirect($redirectUrl);
    
            } else {
               // SSLCommerz::PaymentCancel($request->query('tran_id'));

                $redirectUrl = 'https://elvankitchen.com/payment?status=Canceled"';
           
            return redirect($redirectUrl)->withErrors([
                'error' => 'Payment is Cancelled.',
            ]); 
            }
            }catch(Exception $e){
                return redirect()->back()->with("error", $e->getMessage());
            }
        }
    
        public function cancel(Request $request)
        {
          try{
            $stripe_credentials = DB::table('stripe_credentials')->first();
            if(isset($request->session_id)) {
                $stripe = new StripeClient($stripe_credentials->apiKey);
                $response = $stripe->checkout->sessions->retrieve($request->session_id);
                $tranId = $request->query('tranId');
               StripePayment::stripePaymentCancelled($tranId);
               $redirectUrl = 'https://elvankitchen.com/payment?status=Canceled';
               return redirect($redirectUrl)->withErrors([
                   'error' => 'Payment is Cancelled.',
               ]);     
            }
            return $this->sendError("Payment is canceled.", 'Payments canceled.');
          }catch(Exception $e){
            return redirect()->back()->with("error", $e->getMessage());
            }

        }
}
