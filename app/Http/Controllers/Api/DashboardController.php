<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // For more complex queries
use Carbon\Carbon; // For date manipulation
use Exception;
use Illuminate\Support\Facades\Log;

class DashboardController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('permission:view dashboard_summary')->only(['summary', 'salesTrends']);
        // Add other permissions for other dashboard methods if any
    }

    /**
     * Get summary data for the dashboard.
     */
    public function summary(Request $request)
    {
        try {
            $user = Auth::user();
            $storeIdFilter = null;
            $isSuperAdminGlobalView = false;
            $storeName = 'N/A'; // Default store name

            if ($user->hasRole('super-admin')) {
                if ($request->filled('store_id_filter') && !empty($request->store_id_filter)) {
                    $storeIdFilter = $request->store_id_filter;
                    $store = Store::find($storeIdFilter);
                    if (!$store) return $this->notFoundResponse('Specified store not found.');
                    $storeName = $store->name;
                } else {
                    $isSuperAdminGlobalView = true;
                    $storeName = 'All Stores (System Overview)';
                }
            } elseif ($user->store_id) {
                $storeIdFilter = $user->store_id;
                $storeName = $user->store->name ?? 'User Store'; // Get user's store name
            } else {
                return $this->forbiddenResponse('User not assigned to a store.');
            }

            $summaryData = [];
            $summaryData['store_info'] = ['id' => $storeIdFilter, 'name' => $storeName];

            // Sales Stats
            $todaySalesQuery = Sale::query()->whereDate('created_at', Carbon::today());
            $thisMonthSalesQuery = Sale::query()->whereYear('created_at', Carbon::now()->year)->whereMonth('created_at', Carbon::now()->month);
            $totalSalesQueryForScope = Sale::query();

            if ($storeIdFilter) {
                $todaySalesQuery->where('store_id', $storeIdFilter);
                $thisMonthSalesQuery->where('store_id', $storeIdFilter);
                $totalSalesQueryForScope->where('store_id', $storeIdFilter);
            }
            if ($user->hasRole('sales-person')) { // Scope for salesperson
                $todaySalesQuery->where('user_id', $user->id);
                $thisMonthSalesQuery->where('user_id', $user->id);
                $totalSalesQueryForScope->where('user_id', $user->id);
            }

            $summaryData['sales_today'] = (float) $todaySalesQuery->sum('grand_total'); // Ensure this is 'grand_total'
            $summaryData['today_sales_count'] = (int) (clone $todaySalesQuery)->count(); // Count for today
            $summaryData['sales_this_month'] = (float) $thisMonthSalesQuery->sum('grand_total');
            $summaryData['sales_total_all_time_for_scope'] = (float) $totalSalesQueryForScope->sum('grand_total');
            $summaryData['total_orders_for_scope'] = (int) (clone $totalSalesQueryForScope)->count();

            // Product and Customer stats
            $productQuery = Product::query();
            $customerQuery = Customer::query();

            if ($storeIdFilter) {
                $productQuery->where('store_id', $storeIdFilter);
                $customerQuery->where('store_id', $storeIdFilter);
            }

            $summaryData['products_total'] = (clone $productQuery)->count();
            $summaryData['products_low_stock'] = (clone $productQuery)->whereNotNull('low_stock_threshold')->whereRaw('stock_quantity <= low_stock_threshold')->count();
            $summaryData['products_out_of_stock'] = (clone $productQuery)->where('stock_quantity', '<=', 0)->count();
            $summaryData['customers_total'] = $customerQuery->count();

            if ($isSuperAdminGlobalView) {
                $summaryData['total_system_stores'] = Store::count();
                $summaryData['total_system_users'] = User::count(); // Consider filtering active users if needed
            }
            $summaryData['last_updated'] = now()->toDateTimeString();

            return $this->successResponse($summaryData, 'Dashboard summary fetched successfully.');
        } catch (Exception $e) {
            Log::error('Error fetching dashboard summary: ' . $e->getMessage(), ['user_id' => Auth::id(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Could not fetch dashboard summary.', 500);
        }
    }

    /**
     * Get data for sales trends (e.g., daily sales for last 30 days).
     */
    public function salesTrends(Request $request)
    {
        try {
            $user = Auth::user();
            $storeId = $user->store_id;

            if ($user->hasRole('super-admin') && $request->has('store_id_filter')) {
                $storeId = $request->store_id_filter;
            } elseif ($user->hasRole('super-admin') && !$request->has('store_id_filter')) {
                $storeId = null;
            } elseif (!$user->hasRole('super-admin') && !$storeId) {
                return $this->forbiddenResponse('User not assigned to a store.');
            }

            $period = $request->input('period', 'last_30_days'); // e.g., last_7_days, last_30_days, this_month, last_month, last_12_months
            $startDate = Carbon::now()->subDays(29)->startOfDay(); // Default to last 30 days
            $endDate = Carbon::now()->endOfDay();
            $dateFormat = '%Y-%m-%d'; // For daily grouping
            $dateLabelFormat = 'M d'; // For chart labels

            switch ($period) {
                case 'last_7_days':
                    $startDate = Carbon::now()->subDays(6)->startOfDay();
                    break;
                case 'this_month':
                    $startDate = Carbon::now()->startOfMonth();
                    break;
                case 'last_month':
                    $startDate = Carbon::now()->subMonthNoOverflow()->startOfMonth();
                    $endDate = Carbon::now()->subMonthNoOverflow()->endOfMonth();
                    break;
                case 'last_12_months':
                    $startDate = Carbon::now()->subMonthsNoOverflow(11)->startOfMonth();
                    $dateFormat = '%Y-%m'; // For monthly grouping
                    $dateLabelFormat = 'M Y';
                    break;
            }

            $salesQuery = Sale::query()
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as date_label_group"),
                    DB::raw('SUM(grand_total) as total_sales_amount'),
                    DB::raw('COUNT(id) as total_orders_count')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date_label_group')
                ->orderBy('date_label_group', 'asc');

            if ($storeId) {
                $salesQuery->where('store_id', $storeId);
            }

            $salesData = $salesQuery->get();

            // Prepare data for charts
            $labels = [];
            $salesAmounts = [];
            $orderCounts = [];

            // Create a complete date range for labels to fill gaps
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                $formattedDateKey = $currentDate->format($dateFormat == '%Y-%m-%d' ? 'Y-m-d' : 'Y-m');
                $labels[$formattedDateKey] = $currentDate->format($dateLabelFormat);
                $salesAmounts[$formattedDateKey] = 0;
                $orderCounts[$formattedDateKey] = 0;
                if ($dateFormat == '%Y-%m-%d') {
                    $currentDate->addDay();
                } else {
                    $currentDate->addMonth();
                }
            }

            foreach ($salesData as $data) {
                // The date_label_group from DB should match the key format used above
                if (isset($labels[$data->date_label_group])) {
                    $salesAmounts[$data->date_label_group] = (float)$data->total_sales_amount;
                    $orderCounts[$data->date_label_group] = (int)$data->total_orders_count;
                }
            }


            return $this->successResponse([
                'labels' => array_values($labels),
                'datasets' => [
                    [
                        'label' => 'Total Sales Amount',
                        'data' => array_values($salesAmounts),
                        'borderColor' => '#4CAF50', // Example color
                        'fill' => false,
                    ],
                    [
                        'label' => 'Total Orders',
                        'data' => array_values($orderCounts),
                        'borderColor' => '#2196F3', // Example color
                        'fill' => false,
                    ]
                ],
                'period_start' => $startDate->toDateString(),
                'period_end' => $endDate->toDateString(),
            ], 'Sales trend data fetched successfully.');
        } catch (Exception $e) {
            Log::error('Error fetching sales trends: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Could not fetch sales trends.', 500);
        }
    }
}
