<?php
namespace App\Notifications;

use App\Models\Sale; // Sale model
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSaleNotification extends Notification implements ShouldQueue // Implement ShouldQueue for background sending
{
    use Queueable;

    public Sale $sale; // Public property to hold the Sale model instance

    /**
     * Create a new notification instance.
     */
    public function __construct(Sale $sale)
    {
        $this->sale = $sale;
    }

    /**
     * Get the notification's delivery channels.
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // $notifiable will be the User model instance (Store Admin)
        // We can check user's preferences here to decide channels
        return ['mail']; // Send via email. Could also be ['database'] for in-app.
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $storeName = $this->sale->store->name ?? 'Your Store';
        $invoiceUrl = route('api.sales.show', $this->sale->id); // Example route to view sale, adapt as needed

        return (new MailMessage)
                    ->subject("New Sale Recorded at {$storeName} (Invoice: {$this->sale->invoice_number})")
                    ->greeting("Hello {$notifiable->name},")
                    ->line("A new sale (Invoice: {$this->sale->invoice_number}) amounting to {$this->sale->grand_total} has been recorded at {$storeName}.")
                    ->line("Sale Details:")
                    ->line("- Customer: " . ($this->sale->customer->name ?? 'N/A'))
                    ->line("- Total Items: " . $this->sale->items->sum('quantity'))
                    ->action('View Sale Details', url($invoiceUrl)) // Make sure this URL is accessible/useful
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification. (For database channel)
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'sale_id' => $this->sale->id,
            'invoice_number' => $this->sale->invoice_number,
            'grand_total' => $this->sale->grand_total,
            'store_name' => $this->sale->store->name ?? 'N/A',
            'message' => "New sale #{$this->sale->invoice_number} recorded.",
        ];
    }
}