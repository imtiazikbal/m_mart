<?php
namespace App\Helper;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SSLCommerz
{
    public static function InitiatePayment($payable, $tran_id, $userPhone)
    {

        try {
            $ssl =DB::table('payment_method_credentials')->first();

            // Ensure that SSLCommerz account details are present
            if (!$ssl) {
                return response()->json([
                'status' => 'error',
                'message'=> "Payment credentials not found",
                ], 500);
            }
    
 $base_url = env('APP_ENV') === 'production'
        ? 'https://securepay.sslcommerz.com'
        : 'https://sandbox.sslcommerz.com';

    // $init_url = 'https://securepay.sslcommerz.com/gwprocess/v3/api.php';
    $init_url = 'https://sandbox.sslcommerz.com/gwprocess/v3/api.php';
        

    $validation_url =env('APP_ENV') === 'production'
        ? 'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php'
        : 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php';

    $refund_url = env('APP_ENV') === 'production'
        ? 'https://securepay.sslcommerz.com/gwprocess/v3/api.php'
        : 'https://sandbox.sslcommerz.com/gwprocess/v3/api.php';

    $refund_query_url = env('APP_ENV') === 'production'
        ? 'https://securepay.sslcommerz.com/gwprocess/v3/api.php'
        : 'https://sandbox.sslcommerz.com/gwprocess/v3/api.php';

    $transaction_query_session_url =env('APP_ENV') === 'production'
        ? 'https://securepay.sslcommerz.com/gwprocess/v3/api.php'
        : 'https://sandbox.sslcommerz.com/gwprocess/v3/api.php';
    $transaction_query_id_url =  env('APP_ENV') === 'production'
        ? 'https://securepay.sslcommerz.com/gwprocess/v3/api.php'
        : 'https://sandbox.sslcommerz.com/gwprocess/v3/api.php';
            $response = Http::asForm()->post( $init_url, [
                "store_id" => $ssl->ssl_store_id ?? 'imtia67cffb01877ac',
                "store_passwd" => $ssl->ssl_store_passwd ?? 'imtia67cffb01877ac@ssl',
                "total_amount" => $payable,
                "currency" => "BDT",
                "tran_id" => $tran_id,
                "success_url" => route('ssl.success', ['tran_id' => $tran_id]),
                "fail_url" => route('ssl.fail', ['tran_id' => $tran_id]),
                "cancel_url" => route('ssl.cancel', ['tran_id' => $tran_id]),
                "ipn_url" => $ssl->ipn_url ?? '',
                "cus_name" => "Mehranmartbd",
                "cus_email" => $userPhone,
                "cus_add1" => "Dhaka",
                "cus_add2" => "Dhaka",
                "cus_city" => "Dhaka",
                "cus_state" => "Dhaka",
                "cus_postcode" => "1200",
                "cus_country" => "Bangladesh",
                "cus_phone" => $userPhone,
                "cus_fax" => $userPhone,
                "shipping_method" => "YES",
                "ship_name" => "Dhaka",
                "ship_add1" => "Dhaka",
                "ship_add2" => "Dhaka",
                "ship_city" => "Dhaka",
                "ship_state" => "Dhaka",
                "ship_country" => "Bangladesh",
                "ship_postcode" => "1200",
                "product_name" => "Mehranmartbd",
                "product_category" => "Mehranmartbd",
                "product_profile" => "Mehranmartbd",
                "product_amount" => $payable,
            ]);

            if ($response->successful()) {
                $paymentResponse = $response->json();
                if (isset($paymentResponse['GatewayPageURL'])) {
                    // return ['status' => 'success', 'url' => $paymentResponse['GatewayPageURL']];
                    return $paymentResponse['GatewayPageURL'];
                } else {
                    Log::error('SSLCommerz response did not contain GatewayPageURL', ['response' => $paymentResponse]);
                    return ['status' => 'error', 'message' => 'Payment initiation failed.'];
                }
            } else {
                Log::error('SSLCommerz HTTP request failed', ['response' => $response->body()]);
                return ['status' => 'error', 'message' => 'Payment initiation failed.'];
            }
        } catch (Exception $e) {
            Log::error('Exception during payment initiation', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['status' => 'error', 'message' => 'An error occurred during payment initiation.' .$e->getMessage()];
        }
    }

    public static function PaymentSuccess(string $tran_id,string $val_id)
    {
        $ssl =DB::table('payment_method_credentials')->first();
            // Ensure that SSLCommerz account details are present
            if (!$ssl) {
                return response()->json([
                'status' => 'error',
                'message'=> "Payment credentials not found",
                ], 500);
            }
     $validationUrl =  env('APP_ENV') === 'production'
    ? "https://securepay.sslcommerz.com/validator/api/validationserverAPI.php?val_id=$val_id&store_id=$ssl->ssl_store_id&store_passwd=$ssl->ssl_store_passwd&v=1&format=json"
    : "https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php?val_id=$val_id&store_id=$ssl->ssl_store_id&store_passwd=$ssl->ssl_store_passwd&v=1&format=json";


        // Fetch transaction details from SSLCOMMERZ
        $response = Http::get($validationUrl);
        if ($response->successful()) {
            $result = $response->json();

            $transactionDetails = [
                'status' => $result['status'],
                'transaction_date' => $result['tran_date'],
                'transaction_id' => $result['tran_id'],
                'validation_id' => $result['val_id'],
                'amount' => $result['amount'],
                'store_amount' => $result['store_amount'],
                'bank_transaction_id' => $result['bank_tran_id'],
                'card_type' => $result['card_type'],
                'card_number' => $result['card_no'],
                'card_issuer' => $result['card_issuer'],
                'card_brand' => $result['card_brand'],
                'card_issuer_country' => $result['card_issuer_country'],
                'card_issuer_country_code' => $result['card_issuer_country_code']
            ];
        // Validate tran_id and ensure it is a valid transaction
        DB::table('invoices')->where(['tranId' => $tran_id])->update(['paymentStatus' => 'Success','tranId' => $transactionDetails['transaction_id'],'paymentMethod' => $transactionDetails['card_type'],'paymentDetails' => json_encode($transactionDetails)]);
        return response($transactionDetails, 200);

    }
    }
    public static function PaymentCancel($tran_id)
    {
        DB::table('invoices')->where(['tranId' => $tran_id])->update(['paymentStatus' => 'Cancel']);
           
        return response('Payment cancel', 500);
    }

    public static function PaymentFail($tran_id)
    {
        DB::table('invoices')->where(['tranId' => $tran_id])->update(['paymentStatus' => 'Cancel']);
        
        return response('Payment Fail', 404);
    }

}