<?php

namespace App\Http\Controllers\api\v1;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends ResponseController
{
    //getDashboard
    public function getDashboard(Request $request)
    {
        try {
            $totalsOrders = DB::table('invoices')->count();
            $totalsProducts = DB::table('products')->count();
            $totalsCustomers = DB::table('users')->count();
            $totalsRevenue = DB::table('invoices')->where('paymentStatus', 'Success')->sum('payable');

            $totals = [
                'totalsOrders' => (int) $totalsOrders,
                'totalsProducts' => (int) $totalsProducts,
                'totalsCustomers' => (int) $totalsCustomers,
                'totalsRevenue' => (int) $totalsRevenue,
            ];

          // Initialize the sales trend array with dynamic labels and data placeholders
$dailySalesTrend = [
    'labels' => [],
    'data' => [],
];

// Set the start of the period as today
$startOfPeriod = Carbon::now();

// Loop through each day of the past 15-day range to set labels and calculate daily totals
for ($i = 0; $i < 15; $i++) {
    // Get the day by subtracting $i days from the startOfPeriod
    $day = $startOfPeriod->copy()->subDays($i);

    // Generate label for each day in format "Mon"
    $dailySalesTrend['labels'][] = $day->format('d');

    // Calculate total payable for each day
    $dailyTotal = DB::table('invoices')
        ->where('paymentStatus', 'Success')
        ->whereDate('created_at', $day)
        ->sum('payable');

    // Add daily total to the data array
    $dailySalesTrend['data'][] = (int) $dailyTotal;
}

// Reverse the labels and data arrays so that the oldest date is first
$dailySalesTrend['labels'] = array_reverse($dailySalesTrend['labels']);
$dailySalesTrend['data'] = array_reverse($dailySalesTrend['data']);



           // Define fixed status labels
$fixedLabels = ['Pending', 'Delivered', 'Cancelled', 'Returned'];

// Query to count orders by each status
$ordersByStatusData = DB::table('invoices')
    ->select('status', DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();

// Initialize the ordersByStatus structure with labels and data
$ordersByStatus = [
    'labels' => $fixedLabels,
    'data' => array_fill(0, count($fixedLabels), 0), // Initialize the data with 0 for each status
];

// Map the query results to a status count
foreach ($ordersByStatusData as $statusData) {
    // Find the index of the status in the fixed labels array
    $index = array_search(ucfirst($statusData->status), $fixedLabels);

    // If the status exists in the fixed labels, update its count
    if ($index !== false) {
        $ordersByStatus['data'][$index] = (int) $statusData->count;
    }
}






            // Top selling products
            // Query to get the top 5 selling products by total sales quantity
            $topSellingProductsData = DB::table('invoice_products')
                ->join('products', 'invoice_products.productId', '=', 'products.id') // Join with products table
                ->select('products.title as productName', DB::raw('SUM(invoice_products.quantity) as totalSales'))
                ->groupBy('products.id', 'products.title')
                ->orderByDesc('totalSales') // Order by sales in descending order
                ->limit(5) // Limit to top 5 products
                ->get();

// Initialize the topSellingProducts structure with labels and data
            $topSellingProducts = [
                'labels' => [],
                'data' => [],
            ];

// Populate the labels and data arrays
            foreach ($topSellingProductsData as $productData) {
                $topSellingProducts['labels'][] = $productData->productName; // Add product name to labels
                $topSellingProducts['data'][] = (int) $productData->totalSales; // Add total sales count to data
            }

// latest 5 orders
// Query to get the latest 5 orders with required fields
            $latestOrdersData = DB::table('invoices')
                ->join('users', 'invoices.userId', '=', 'users.id') // Join with users table to get customer name
                ->select(
                    'invoices.id as orderId',
                    DB::raw('CONCAT(users.firstName, " ", users.lastName) as customerName'),
                    DB::raw('SUM(invoice_products.quantity) as totalItems'),
                    'invoices.payable as totalAmount',
                    'invoices.created_at as orderDate',
                    'invoices.status'
                )
                ->leftJoin('invoice_products', 'invoices.id', '=', 'invoice_products.invoiceId') // Join with invoice_products to count total items
                ->groupBy('invoices.id', 'users.firstName', 'users.lastName', 'invoices.payable', 'invoices.created_at', 'invoices.status')
                ->orderByDesc('invoices.created_at') // Order by order date (latest first)
                ->limit(5) // Limit to the latest 5 orders
                ->get();

// Initialize the orderList structure
            $orderList = [];

// Populate the order list with the data
            foreach ($latestOrdersData as $order) {
                $orderList[] = [
                    'id' => $order->orderId,
                    'customerName' => $order->customerName,
                    'totalItems' => (int) $order->totalItems,
                    'totalAmount' => (int) $order->totalAmount,
                    'orderDate' => Carbon::parse($order->orderDate)->toIso8601String(), // Convert to ISO format
                    'status' => ucfirst($order->status), // Ensure the status is capitalized
                ];
            }

// Example response structure

            // Prepare the response array
            $response = [
                'totals' => $totals,
                'dailySalesTrend' => $dailySalesTrend,
                'ordersByStatus' => $ordersByStatus,
                'topSellingProducts' => $topSellingProducts,
                'orderList' => $orderList,

            ];

            return $this->sendResponse($response, 'Dashboard data retrieved successfully');

        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), [], 500);

        }
    }
}
