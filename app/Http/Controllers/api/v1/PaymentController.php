<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use Stripe\StripeClient;
use App\Helper\SSLCommerz;
use Illuminate\Http\Request;
use App\Services\StripePayment;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\GeneratesTransactionId;

class PaymentController extends ResponseController
{
        use GeneratesTransactionId;

   public function storePayment(Request $request)
{
    DB::beginTransaction();

    try {
        $userId = $request->header('userID');
        $user = $this->getUser($userId);
        if (!$user) return $this->sendError('User not found', [], 404);

        $cartItems = $request->cartItems ?? [];
        if (empty($cartItems)) return $this->sendError('Cart is empty', [], 400);

        $this->validateProducts($cartItems);

        $cartData = $this->prepareCartData($userId, $cartItems);
        DB::table('product_carts')->insert($cartData);

        $deliveryData = $this->extractDeliveryData($request);
        $cartFromDb = DB::table('product_carts')->where('userId', $userId)->get();

        [$originalPrice, $totalPrice] = $this->calculateTotals($cartFromDb);

        if ($request->filled('coupon')) {
            $totalPrice = $this->applyCouponDiscount($request->coupon, $totalPrice);
        }

        $tranId = $this->generateUniqueTransactionId('invoices', 'tranId');

        $invoiceId = DB::table('invoices')->insertGetId([
            'total' => $originalPrice,
            'vat' => 0,
            'payable' => $totalPrice,
            'cusDetails' => json_encode($deliveryData),
            'tranId' => $tranId,
            'paymentStatus' => 'Pending',
            'userId' => $userId,
            'paymentMethod' => $request->paymentType ?? 'Cash on delivery',
            'status' => 'Pending',
            'coupon' => $request->coupon ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->storeDeliveryAddress($invoiceId, $deliveryData);
        $this->storeInvoiceProducts($invoiceId, $cartData, $userId);

        DB::table('product_carts')->where('userId', $userId)->delete();
        DB::commit();

        if ($request->paymentType === 'ssl') {
            $paymentURL = SSLCommerz::InitiatePayment($totalPrice, $tranId, $user->phone ?? $user->email);
            return $this->sendResponse($paymentURL, 'Payment initiated via SSLCOMMERZ');
        }

        return $this->sendResponse('Payment method created successfully', 'Payment method created successfully');

    } catch (Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message'=> $e->getMessage()
        ], 500);
    }
}


private function getUser($userId)
{
    return DB::table('users')->find($userId);
}

private function validateProducts(array $cartItems)
{
    $productIds = array_column($cartItems, 'productId');
    $existingProductIds = DB::table('products')->whereIn('id', $productIds)->pluck('id')->toArray();
    $missing = array_diff($productIds, $existingProductIds);

    if (!empty($missing)) {
        throw new Exception('Missing products with IDs: ' . implode(', ', $missing));
    }
}

private function prepareCartData($userId, array $items): array
{
    return array_map(function ($item) use ($userId) {
        return [
            'userId' => $userId,
            'productId' => $item['productId'],
            'quantity' => $item['quantity'],
            'discountPercent' => $item['discountPercent'] ?? 0,
            'price' => $item['price'],
            'size' => $item['size'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }, $items);
}

private function extractDeliveryData(Request $request): array
{
    return $request->only([
        'userId', 'firstName', 'lastName', 'email',
        'phone', 'country', 'city', 'address', 'postCode'
    ]);
}

private function calculateTotals($cartItems): array
{
    $originalPrice = 0;
    $totalPrice = 0;

    foreach ($cartItems as $item) {
        $originalPrice += $item->price * $item->quantity;
        $discount = ($item->discountPercent ?? 0) * $item->price / 100;
        $discountedPrice = $item->price - $discount;
        $totalPrice += $discountedPrice * $item->quantity;
    }

    return [$originalPrice, $totalPrice];
}

private function applyCouponDiscount($code, $totalPrice): float
{
    $coupon = DB::table('coupons')->where('couponCode', $code)->first();

    if (!$coupon || ($coupon->discountPercent ?? 0) <= 0) return $totalPrice;

    $discount = $totalPrice * ($coupon->discountPercent / 100);
    return max(0, $totalPrice - $discount);
}

private function storeDeliveryAddress($invoiceId, array $data)
{
    DB::table('delivery_addresses')->insert(array_merge($data, [
        'invoiceId' => $invoiceId,
        'created_at' => now(),
        'updated_at' => now(),
    ]));
}

private function storeInvoiceProducts($invoiceId, array $cartData, $userId)
{
    $products = array_map(function ($product) use ($invoiceId, $userId) {
        return [
            'invoiceId' => $invoiceId,
            'productId' => $product['productId'],
            'userId' => $userId,
            'salePrice' => $product['price'],
            'quantity' => $product['quantity'],
            'size' => $product['size'],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }, $cartData);

    DB::table('invoice_products')->insert($products);
}


















         public function PaymentSuccess(Request $request)
    {
        try {
           $data = SSLCommerz::PaymentSuccess($request->query('tran_id'), $request->input('val_id'));
            return $this->sendResponse($data, 'Payments retrieved successfully.');

        } catch (Exception $e) {
            return $this->sendError('Error creating payment method'.$e->getMessage(), [], 500);
        }
    }

    public function PaymentFail(Request $request)
    {
        try {
            SSLCommerz::PaymentFail($request->query('tran_id'));
            return $this->sendError('Payment fail', [], 500);
        } catch (Exception $e) {
            return $this->sendError('Error creating payment method'.$e->getMessage(), [], 500);
        }
    }
    public function PaymentCancel(Request $request)
    {
        try {
            SSLCommerz::PaymentCancel($request->query('tran_id'));
            // return redirect('/Profile');
            return $this->sendError('Payment cancel', [], 500);
        } catch (Exception $e) {
            return $this->sendError('Error creating payment method'.$e->getMessage(), [], 500);
        }
    }
}
