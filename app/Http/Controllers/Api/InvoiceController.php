<?php

namespace App\Http\Controllers\Api;

use App\Models\Sale;
use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf; // For PDF generation (install if not already: composer require barryvdh/laravel-dompdf)
use Exception;
use Illuminate\Support\Facades\Log;


class InvoiceController extends BaseApiController
{
    public function __construct()
    {
         // Activate permission middleware for invoices
        // 'view sales_history' can often double for viewing invoice data
        // or create a specific 'view invoices' permission if needed.
        $this->middleware('permission:view sales_history')->only(['show']);
        $this->middleware('permission:download invoices')->only(['downloadPDF']);
    }

    /**
     * Display the specified sale as an invoice (JSON data).
     * This can be similar to SalesController@show or a more specific invoice format.
     */
    public function show(Sale $sale) // Using route model binding for Sale
    {
        try {
            $user = Auth::user();
            if (!$user->hasRole('super-admin') && $user->store_id !== $sale->store_id) {
                return $this->forbiddenResponse('You do not have permission to view this invoice.');
            }
            // You might want a specific InvoiceResource if the structure is different from SaleResource
            return $this->successResponse(
                new \App\Http\Resources\SaleResource($sale->load(['store', 'user', 'customer', 'items.product'])),
                'Invoice details fetched successfully.'
            );
        } catch (Exception $e) {
            Log::error("Error fetching invoice (sale) ID {$sale->id}: " . $e->getMessage());
            return $this->errorResponse('Could not fetch invoice details.', 500);
        }
    }

    /**
     * Download the specified sale as a PDF invoice.
     */
    public function downloadPDF(Sale $sale)
    {
        try {
            $user = Auth::user();
            if (!$user->hasRole('super-admin') && $user->store_id !== $sale->store_id) {
                // This will return JSON error, not a PDF. Consider how to handle auth for direct PDF downloads.
                // One way is to generate a temporary signed URL if PDF is served via web route.
                // For API, it's okay to return JSON error.
                return $this->forbiddenResponse('You do not have permission to download this invoice.');
            }

            // Ensure barryvdh/laravel-dompdf is installed: composer require barryvdh/laravel-dompdf
            // And configured (usually auto-discovered)

            // Eager load necessary relationships for the PDF view
            $sale->load(['store', 'user', 'customer', 'items.product']);

            // Create a Blade view for the invoice, e.g., resources/views/pdf/invoice.blade.php
            $pdf = Pdf::loadView('pdf.invoice', compact('sale'));

            // Option 1: Stream the PDF directly
            // return $pdf->stream("invoice-{$sale->invoice_number}.pdf");

            // Option 2: Download the PDF
            return $pdf->download("invoice-{$sale->invoice_number}.pdf");

        } catch (Exception $e) {
            Log::error("Error generating PDF for invoice (sale) ID {$sale->id}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Could not generate PDF invoice. ' . $e->getMessage(), 500);
        }
    }
}