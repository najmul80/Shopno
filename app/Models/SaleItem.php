<?php

namespace App\Models; // Ensure correct namespace

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'item_sub_total',
        // 'item_discount_amount',
        // 'item_tax_amount',
        // 'item_total_amount',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'item_sub_total' => 'decimal:2',
        // 'item_discount_amount' => 'decimal:2',
        // 'item_tax_amount' => 'decimal:2',
        // 'item_total_amount' => 'decimal:2',
    ];

    /**
     * Relationship to the Sale model.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relationship to the Product model.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
