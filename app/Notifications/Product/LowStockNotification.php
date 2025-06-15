<?php

namespace App\Notifications\Product; // Corrected namespace

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Product $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function via(object $notifiable): array
    {
        // Define delivery channels. User preferences can be checked here.
        // Example: if ($notifiable->prefers_mail_for_low_stock) return ['mail', 'database'];
        return ['database', 'mail']; // Send to database and via email
    }

    public function toMail(object $notifiable): MailMessage
    {
        $storeName = $this->product->store->name ?? 'Your Store';
        return (new MailMessage)
                    ->subject("Low Stock Alert for {$this->product->name} at {$storeName}")
                    ->greeting("Hello {$notifiable->name},")
                    ->line("The stock for product '{$this->product->name}' (SKU: {$this->product->sku}) at store '{$storeName}' is running low.")
                    ->line("Current stock: {$this->product->stock_quantity} units.")
                    ->lineIf($this->product->low_stock_threshold > 0, "The low stock threshold is set to: {$this->product->low_stock_threshold} units.")
                    ->action('View Product', url('/api/v1/products/' . $this->product->id)) // Placeholder URL, adjust for frontend
                    ->line('Please take necessary action to restock this item.');
    }

    public function toArray(object $notifiable): array // For database notifications
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'current_stock' => $this->product->stock_quantity,
            'store_id' => $this->product->store_id,
            'store_name' => $this->product->store->name ?? 'N/A',
            'message' => "Stock for '{$this->product->name}' is low ({$this->product->stock_quantity} units).",
        ];
    }
}