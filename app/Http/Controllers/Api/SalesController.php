<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Sales\ProductNotFoundInStoreException;
use App\Exceptions\Sales\StockUnavailableException;
use App\Models\Sale;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\SaleResource;
use App\Http\Requests\Sale\StoreSaleRequest;
use App\Services\SalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class SalesController extends BaseApiController
{
    protected SalesService $salesService;

    public function __construct(SalesService $salesService)
    {
        $this->salesService = $salesService;
        $this->middleware('auth:api');
        // Add permission middleware after defining sales permissions
        $this->middleware('permission:view sales')->only(['index', 'show']);
        $this->middleware('permission:process sales')->only(['store']);
        $this->middleware('permission:cancel sales')->only(['cancel']); // Example for a cancel method
    }

    /**
     * Display a listing of sales.
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $query = Sale::query()->with(['store', 'user', 'customer', 'items.product']);

            // Scoping
            if ($user->hasRole('super-admin') && $request->has('store_id_filter')) {
                $query->where('store_id', $request->store_id_filter);
            } elseif (!$user->hasRole('super-admin') && $user->store_id) {
                $query->where('store_id', $user->store_id);
            } elseif (!$user->hasRole('super-admin') && !$user->store_id) {
                return $this->forbiddenResponse('User not assigned to a store.');
            }

            // Filtering by date range, customer, status etc.
            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }
            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }
            if ($request->filled('sale_status')) {
                $query->where('sale_status', $request->sale_status);
            }
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('created_at', [$request->start_date . " 00:00:00", $request->end_date . " 23:59:59"]);
            }
            if ($request->filled('invoice_number')) {
                $query->where('invoice_number', 'like', "%{$request->invoice_number}%");
            }


            $sales = $query->latest()->paginate($request->input('per_page', 15));
            return SaleResource::collection($sales);
        } catch (Exception $e) {
            Log::error('Error fetching sales: ' . $e->getMessage());
            return $this->errorResponse('Could not fetch sales.', 500);
        }
    }

    /**
     * Store a newly created sale in storage.
     */
    public function store(StoreSaleRequest $request) 
    {
        try {
            $user = Auth::user(); 
            $validatedData = $request->validated();

            $sale = $this->salesService->createSale($validatedData, $user);

            return $this->successResponse(
                new SaleResource($sale), 
                'Sale processed successfully.',
                201 // HTTP 201 Created
            );
        } catch (StockUnavailableException $e) {
            Log::warning('SalesController: Stock unavailable during sale processing.', ['error' => $e->getMessage(), 'request_data' => $request->safe()->all()]);
            return $this->errorResponse($e->getMessage(), 422); // 422 Unprocessable Entity
        } catch (ProductNotFoundInStoreException $e) {
            Log::warning('SalesController: Product not found in store during sale.', ['error' => $e->getMessage(), 'request_data' => $request->safe()->all()]);
            return $this->errorResponse($e->getMessage(), 404); // 404 Not Found (or 422)
        } catch (Exception $e) {
            Log::error('SalesController: Error processing sale.', [
                'error_message' => $e->getMessage(),
                'request_data' => $request->safe()->all(), // Use safe() to get validated data or filter sensitive fields
                'trace_snippet' => substr($e->getTraceAsString(), 0, 500)
            ]);

            // Determine appropriate status code
            $statusCode = 500; // Default to Internal Server Error
            if (method_exists($e, 'getStatusCode')) {
                $sCode = $e->getStatusCode();
                if ($sCode >= 400 && $sCode < 500) $statusCode = $sCode;
            } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
                $statusCode = 422;
            }
            // For specific custom exceptions, you might have already set a status code or message
            // For example, StockUnavailableException might consistently be 422.

            return $this->errorResponse('Could not process sale: ' . $e->getMessage(), $statusCode);
        }
    }

    /**
     * Display the specified sale.
     */
    public function show(Sale $sale) // Route model binding
    {
        try {
            $user = Auth::user();
            // Authorization: User can see sales of their own store, or super-admin can see any.
            if (!$user->hasRole('super-admin') && $user->store_id !== $sale->store_id) {
                return $this->forbiddenResponse('You do not have permission to view this sale.');
            }

            return $this->successResponse(
                new SaleResource($sale->load(['store', 'user', 'customer', 'items.product'])),
                'Sale details fetched successfully.'
            );
        } catch (Exception $e) {
            Log::error("Error fetching sale details for ID {$sale->id}: " . $e->getMessage());
            return $this->errorResponse('Could not fetch sale details.', 500);
        }
    }

    // update() and destroy() for Sales are usually more complex (e.g., returns, cancellations)
    // and might involve reversing stock, payments, etc.
    // For now, we'll omit direct update/delete of sales.
}
