<?php
namespace App\Observers;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Product\LowStockNotification; 
// use Illuminate\Support\Facades\Notification; 

class ProductObserver
{
    public function updated(Product $product): void
    {
        // Check if stock quantity changed and dropped below or to the threshold
        if ($product->isDirty('stock_quantity')) {
            $originalStock = $product->getOriginal('stock_quantity');
            $newStock = $product->stock_quantity;
            $threshold = $product->low_stock_threshold ?? 0; // Default to 0 if null

            if ($newStock <= $threshold && $originalStock > $threshold) {
                // Stock has dropped to or below the threshold
                $storeAdmins = User::where('store_id', $product->store_id)
                                   ->whereHas('roles', fn($q) => $q->whereIn('name', ['store-admin', 'super-admin']))
                                   ->get();
                if ($storeAdmins->isNotEmpty()) {
                    // Notification::send($storeAdmins, new LowStockNotification($product));
                    foreach($storeAdmins as $admin){
                         $admin->notifyNow(new LowStockNotification($product->loadMissing('store')));
                    }
                }
            }
        }
    }
}