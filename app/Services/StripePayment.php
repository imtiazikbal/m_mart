<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;



class StripePayment
{
   
    public static function stripePaymentSuccess($tranId) {
        
        // Find the invoice ny TranId
        $findInvoice = DB::table('invoices')
        ->where('id', $tranId)
        ->first();

        if($findInvoice) {
           DB::table('invoices')
            ->where('id', $tranId)
            ->update([
                'paymentStatus' => 'Success'
            ]);
        }else{
            return false;
        }
       
    }


    public static function stripePaymentFailed($tranId) {
        
        // Find the invoice ny TranId
        $findInvoice = DB::table('invoices')
        ->where('id', $tranId)
        ->first();

        if($findInvoice) {
           DB::table('invoices')
            ->where('id', $tranId)
            ->update([
                'paymentStatus' => 'Failed'
            ]);
        }else{
            return false;
        }
       
    }

    public static function stripePaymentCancelled($tranId) {
        // Find the invoice ny TranId
        $findInvoice = DB::table('invoices')
        ->where('id', $tranId)
        ->first();
        if($findInvoice) {
           DB::table('invoices')
            ->where('id', $tranId)
            ->update([
                'paymentStatus' => 'Cancelled'
            ]);
        }else{
            return false;
        }
       
    }


}
