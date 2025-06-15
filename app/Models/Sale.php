<?php

namespace App\Models; // Ensure correct namespace

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'store_id',
        'user_id',
        'customer_id',
        'sub_total_amount',
        'discount_amount',
        'discount_type',
        'tax_amount',
        // 'tax_details',
        'shipping_charge',
        'grand_total_amount',
        'paid_amount',
        'due_amount',
        'payment_status',
        'payment_method',
        // 'transaction_reference',
        'sale_status',
        'notes',
    ];

    protected $casts = [
        'sub_total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_charge' => 'decimal:2',
        'grand_total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        // 'tax_details' => 'array',
    ];

    /**
     * Relationship to the Store model.
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Relationship to the User model (staff who made the sale).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship to the Customer model.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship to SaleItem model.
     * A sale has many items.
     */
    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    // You can add helper methods here, e.g., to calculate totals if not done before saving
    // protected static function boot()
    // {
    //     parent::boot();
    //     static::creating(function ($sale) {
    //         // Logic to calculate due_amount or other fields before saving
    //         $sale->due_amount = $sale->grand_total_amount - $sale->paid_amount;
    //         if (empty($sale->invoice_number)) {
    //             // $sale->invoice_number = self::generateInvoiceNumber($sale->store_id); // Implement this method
    //         }
    //     });
    //     static::updating(function ($sale) {
    //         $sale->due_amount = $sale->grand_total_amount - $sale->paid_amount;
    //     });
    // }
}