<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception; // General Exception
// You can define custom exceptions for more specific error handling
// e.g., App\Exceptions\StockUnavailableException;
// e.g., App\Exceptions\ProductNotFoundInStoreException;

class SalesService
{
    /**
     * Create a new sale.
     *
     * @param array $validatedData Data validated by StoreSaleRequest.
     * @param User $user The user performing the sale.
     * @return Sale The created Sale object.
     * @throws Exception If any error occurs.
     */
    public function createSale(array $validatedData, User $user): Sale
    {
        DB::beginTransaction(); // Start a database transaction

        try {
            $storeId = null;
            if ($user->hasRole('super-admin') && isset($validatedData['store_id'])) {
                $storeId = $validatedData['store_id'];
                if (!Store::find($storeId)) {
                    throw new Exception("Specified store (ID: {$storeId}) not found for super admin."); // Or a custom exception
                }
            } elseif ($user->store_id) {
                $storeId = $user->store_id;
            } else {
                // This case should ideally be caught by StoreSaleRequest or controller logic before service
                throw new Exception("User is not associated with a store, and no store_id provided (if super-admin).");
            }

            // --- Item Processing & SubTotal Calculation ---
            $subTotal = 0;
            $saleItemsToCreate = [];
            $productsToUpdateStock = [];

            foreach ($validatedData['items'] as $item) {
                $product = Product::where('id', $item['product_id'])
                                  ->where('store_id', $storeId) // Ensure product belongs to the correct store
                                  ->first();

                if (!$product) {
                    // throw new ProductNotFoundInStoreException("Product with ID {$item['product_id']} not found in store ID {$storeId}.");
                    throw new Exception("Product '{$item['product_name_for_error_message_if_available']}' (ID: {$item['product_id']}) not found or does not belong to store ID {$storeId}.");
                }

                if ($product->stock_quantity < $item['quantity']) {
                    // throw new StockUnavailableException("Not enough stock for product: {$product->name}. Available: {$product->stock_quantity}, Requested: {$item['quantity']}");
                    throw new Exception("Not enough stock for product: {$product->name} (SKU: {$product->sku}). Available: {$product->stock_quantity}, Requested: {$item['quantity']}");
                }

                $itemSubTotal = $product->sale_price * $item['quantity'];
                $subTotal += $itemSubTotal;

                $saleItemsToCreate[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->sale_price, // Price at the time of sale
                    'item_sub_total' => $itemSubTotal,
                ];

                $productsToUpdateStock[] = [
                    'id' => $product->id,
                    'decrement_by' => $item['quantity'],
                ];
            }

            // --- Total Calculation ---
            $discountAmount = (float)($validatedData['discount_amount'] ?? 0.00);
            $taxPercentage = (float)($validatedData['tax_percentage'] ?? 0.00);
            $shippingCharge = (float)($validatedData['shipping_charge'] ?? 0.00);

            // Tax is usually calculated on (SubTotal - Discount)
            $taxableAmount = $subTotal - $discountAmount;
            $taxAmount = $taxableAmount > 0 ? ($taxableAmount * ($taxPercentage / 100)) : 0.00;

            $grandTotal = ($subTotal - $discountAmount) + $taxAmount + $shippingCharge;

            // Determine amount_paid and change_returned
            $amountPaid = (float)($validatedData['amount_paid'] ?? $grandTotal);
            $changeReturned = $amountPaid - $grandTotal;
            if ($changeReturned < 0) $changeReturned = 0; // No negative change

            // --- Create Sale Record ---
            $sale = Sale::create([
                'invoice_number' => $this->generateInvoiceNumber($storeId),
                'store_id' => $storeId,
                'user_id' => $user->id,
                'customer_id' => $validatedData['customer_id'] ?? null,
                'sub_total' => $subTotal,
                'discount_amount' => $discountAmount,
                'discount_type' => $validatedData['discount_type'] ?? null,
                'tax_percentage' => $taxPercentage,
                'tax_amount' => $taxAmount,
                'shipping_charge' => $shippingCharge,
                'grand_total' => $grandTotal,
                'amount_paid' => $amountPaid,
                'change_returned' => $changeReturned,
                'payment_method' => $validatedData['payment_method'] ?? 'cash',
                'payment_status' => $validatedData['payment_status'] ?? ($amountPaid >= $grandTotal ? 'paid' : 'pending'),
                'sale_status' => $validatedData['sale_status'] ?? 'completed',
                'notes' => $validatedData['notes'] ?? null,
            ]);

            // --- Create Sale Items ---
            if (!empty($saleItemsToCreate)) {
                $sale->items()->createMany($saleItemsToCreate);
            }

            // --- Update Product Stock ---
            foreach ($productsToUpdateStock as $stockUpdate) {
                Product::where('id', $stockUpdate['id'])->decrement('stock_quantity', $stockUpdate['decrement_by']);
            }

            DB::commit(); // Commit the transaction

            // --- Post-Sale Actions (e.g., Notifications, Activity Log) ---
            // Activity log can be done here or in SaleObserver
            activity()->causedBy($user)->performedOn($sale)->log('Sale processed: ' . $sale->invoice_number);

            // Send notification (example)
            try {
                $storeAdmins = User::where('store_id', $sale->store_id)
                                   ->whereHas('roles', fn($q) => $q->where('name', 'store-admin'))
                                   ->get();
                if ($storeAdmins->isNotEmpty()) {
                    // Notification::send($storeAdmins, new NewSaleNotification($sale));
                    foreach ($storeAdmins as $admin) {
                        $admin->notifyNow(new \App\Notifications\NewSaleNotification($sale->loadMissing('store', 'customer', 'items.product')));
                        // notifyNow for immediate sending, or notify for queueing
                    }
                }
            } catch (Exception $e) {
                Log::error("SalesService: Failed to send new sale notification for sale ID: {$sale->id}", ['error' => $e->getMessage()]);
                // Do not let notification failure roll back the main transaction.
            }

            return $sale;

        } catch (Exception $e) {
            DB::rollBack(); // Rollback transaction on any error
            Log::error('SalesService::createSale failed.', [
                'error_message' => $e->getMessage(),
                'user_id' => $user->id,
                'validated_data' => $validatedData, // Be careful with sensitive data logging
                'trace' => $e->getTraceAsString(),
            ]);
            // Re-throw the exception to be caught by the controller,
            // or throw a more specific custom exception.
            throw $e;
        }
    }

    /**
     * Generate a unique invoice number.
     * (This logic can be customized based on requirements)
     * @param int $storeId
     * @return string
     */
    protected function generateInvoiceNumber(int $storeId): string
    {
        $store = Store::find($storeId);
        // Use a more robust prefix, perhaps from store settings or a shorter code
        $prefix = $store ? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $store->name), 0, 3)) : 'INV';
        $prefix = $prefix ?: 'INV'; // Ensure prefix is not empty

        $datePart = now()->format('ymd'); // Shortened year

        // Get the count of sales for this store on this date to generate a sequence number
        // This approach can have race conditions under high load.
        // A dedicated sequence table or a more robust unique ID generator might be better.
        $dailySequence = Sale::where('store_id', $storeId)
                             ->whereDate('created_at', today())
                             ->count() + 1; // Next sequence number

        $invoiceNumber = $prefix . '-' . $datePart . '-' . str_pad($dailySequence, 4, '0', STR_PAD_LEFT);

        // Ensure uniqueness (in case of race condition, retry with a new sequence/random element)
        while (Sale::where('invoice_number', $invoiceNumber)->exists()) {
            $dailySequence++; // Increment sequence
            $invoiceNumber = $prefix . '-' . $datePart . '-' . str_pad($dailySequence, 4, '0', STR_PAD_LEFT);
            // Or add a random suffix if duplicates are frequent due to high concurrency
            // $invoiceNumber = $prefix . '-' . $datePart . '-' . str_pad($dailySequence, 4, '0', STR_PAD_LEFT) . '-' . Str::upper(Str::random(2));
        }
        return $invoiceNumber;
    }
}