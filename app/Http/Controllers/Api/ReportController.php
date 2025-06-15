<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Sale;
use App\Models\Product;
use App\Http\Resources\SaleResource;
use App\Http\Resources\ProductResource; // For stock report
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ReportController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('permission:view sales_reports_advanced')->only(['salesHistory']);
        $this->middleware('permission:view stock_reports')->only(['stockLevels']);
    }

    /**
     * Get sales history report.
     */
    public function salesHistory(Request $request)
    {
        try {
            $user = Auth::user();
            $query = Sale::query()->with(['store', 'user', 'customer', 'items.product']); // Eager load
            $storeId = $user->store_id;

            if ($user->hasRole('super-admin') && $request->has('store_id_filter')) {
                $storeId = $request->store_id_filter;
            } elseif ($user->hasRole('super-admin') && !$request->has('store_id_filter')) {
                $storeId = null; // Super admin sees all if no filter
            } elseif (!$user->hasRole('super-admin') && !$storeId) {
                return $this->forbiddenResponse('User not assigned to a store.');
            }

            if ($storeId) {
                $query->where('store_id', $storeId);
            }

            // Date filtering
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = Carbon::parse($request->end_date)->endOfDay();
                $query->whereBetween('created_at', [$startDate, $endDate]);
            } elseif ($request->filled('date')) { // Single date
                $query->whereDate('created_at', Carbon::parse($request->date));
            }


            // Other filters (customer_id, payment_status, sale_status) can be added as in SalesController@index
            if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);
            if ($request->filled('payment_status')) $query->where('payment_status', $request->payment_status);
            if ($request->filled('sale_status')) $query->where('sale_status', $request->sale_status);

            $sales = $query->latest()->get(); // Get all matching records for report, or paginate if too large

            if ($request->input('format') === 'pdf') {
                // Create a Blade view for sales history PDF, e.g., resources/views/pdf/sales_history_report.blade.php
                $pdf = Pdf::loadView('pdf.reports.sales_history', compact('sales', 'startDate', 'endDate', 'storeId'));
                return $pdf->download('sales-history-report-' . now()->format('Y-m-d') . '.pdf');
            }

            return SaleResource::collection($sales);
        } catch (Exception $e) {
            Log::error('Error generating sales history report: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Could not generate sales history report.', 500);
        }
    }

    /**
     * Get stock levels report.
     */
    public function stockLevels(Request $request)
    {
        try {
            $user = Auth::user();
            $query = Product::query()->with(['store', 'category']); // Eager load
            $storeId = $user->store_id;

            if ($user->hasRole('super-admin') && $request->has('store_id_filter')) {
                $storeId = $request->store_id_filter;
            } elseif ($user->hasRole('super-admin') && !$request->has('store_id_filter')) {
                $storeId = null;
            } elseif (!$user->hasRole('super-admin') && !$storeId) {
                return $this->forbiddenResponse('User not assigned to a store.');
            }

            if ($storeId) {
                $query->where('store_id', $storeId);
            }

            // Filtering by category, stock status (low, out_of_stock)
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            if ($request->input('stock_status') === 'low_stock') {
                $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                    ->where('stock_quantity', '>', 0);
            } elseif ($request->input('stock_status') === 'out_of_stock') {
                $query->where('stock_quantity', '<=', 0);
            }

            $products = $query->orderBy('name')->get(); // Get all products for report, or paginate

            if ($request->input('format') === 'pdf') {
                // Create a Blade view for stock levels PDF, e.g., resources/views/pdf/stock_levels_report.blade.php
                $pdf = Pdf::loadView('pdf.reports.stock_levels', compact('products', 'storeId'));
                return $pdf->download('stock-levels-report-' . now()->format('Y-m-d') . '.pdf');
            }

            return ProductResource::collection($products);
        } catch (Exception $e) {
            Log::error('Error generating stock levels report: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Could not generate stock levels report.', 500);
        }
    }

    public function topSellingProducts(Request $request)
    {
        try {
            $user = Auth::user();
            $storeId = null; // For store scoping

            if ($user->hasRole('super-admin') && $request->filled('store_id_filter')) {
                $storeId = $request->store_id_filter;
                if ($storeId && !Store::find($storeId)) { // Check if store_id is valid if provided
                    return $this->notFoundResponse('Specified store not found.');
                }
            } elseif (!$user->hasRole('super-admin') && $user->store_id) {
                $storeId = $user->store_id;
            } elseif (!$user->hasRole('super-admin') && !$user->store_id) {
                return $this->forbiddenResponse('User not assigned to a store.');
            }
            // If super-admin and no store_id_filter, $storeId remains null (all stores)

            $period = $request->input('period', 'this_month'); // Default to this month
            $limit = (int) $request->input('limit', 5); // Default to top 5 products

            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();

            switch ($period) {
                case 'last_7_days':
                    $startDate = Carbon::now()->subDays(6)->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    break;
                case 'last_30_days':
                    $startDate = Carbon::now()->subDays(29)->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    break;
                case 'last_month':
                    $startDate = Carbon::now()->subMonthNoOverflow()->startOfMonth();
                    $endDate = Carbon::now()->subMonthNoOverflow()->endOfMonth();
                    break;
                case 'this_month':
                    // Default, already set
                    break;
            }

            $topProductsQuery = Product::query()
                ->select('products.*', DB::raw('SUM(sale_items.quantity) as total_quantity_sold'))
                ->join('sale_items', 'products.id', '=', 'sale_items.product_id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->whereBetween('sales.created_at', [$startDate, $endDate])
                ->groupBy('products.id') // Group by all selected columns from products table for compatibility
                ->orderByDesc('total_quantity_sold')
                ->limit($limit);

            // Group by all non-aggregated columns from products table
            $productColumns = Schema::getColumnListing('products'); // Get all columns
            foreach ($productColumns as $column) {
                $topProductsQuery->groupBy("products.{$column}");
            }


            if ($storeId) {
                $topProductsQuery->where('products.store_id', $storeId); // Filter by product's store_id
            }

            $topSellingProducts = $topProductsQuery->with(['store', 'category', 'images'])->get(); // Eager load relations for ProductResource
            $formattedProducts = $topSellingProducts->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'sku' => $product->sku,
                    'primary_image_url' => $product->primary_image_url, // Accessor from Product model
                    'total_quantity_sold' => $product->total_quantity_sold, // The aggregated value
                    // Add other necessary fields from ProductResource if needed for display
                    'sale_price' => (float) $product->sale_price,
                ];
            });
            return $this->successResponse($formattedProducts, 'Top selling products fetched successfully.');
        } catch (Exception $e) {
            Log::error('Error fetching top selling products: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'params' => $request->all()
            ]);
            return $this->errorResponse('Could not fetch top selling products.', 500);
        }
    }
}
