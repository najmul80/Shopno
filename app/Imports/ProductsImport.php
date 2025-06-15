<?php

namespace App\Imports; // Ensure correct namespace

use App\Models\Product;
use App\Models\Category; // To find category by name/slug
use App\Models\Store;    // To associate with store
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; // For slug generation
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // To use heading row names as keys
use Maatwebsite\Excel\Concerns\WithBatchInserts; // For better performance with large files
use Maatwebsite\Excel\Concerns\WithChunkReading; // For reading file in chunks
use Maatwebsite\Excel\Concerns\SkipsOnError; // To skip rows that cause errors and continue
use Maatwebsite\Excel\Concerns\SkipsErrors; // To collect errors
use Maatwebsite\Excel\Concerns\WithValidation; // To validate rows
use Maatwebsite\Excel\Validators\Failure; // For handling validation failures
use Throwable; // For catching general errors during skip

class ProductsImport implements
    ToModel,
    WithHeadingRow,
    WithBatchInserts,
    WithChunkReading,
    SkipsOnError, // Implement SkipsOnError
    WithValidation  // Implement WithValidation
{
    use SkipsErrors; // Use SkipsErrors trait to access errors

    private $storeId;
    private $user;
    public $importedRowCount = 0;
    public $skippedRowCount = 0;
    public $errorMessages = [];

    /**
     * Constructor to pass necessary data like store_id.
     * @param int|null $storeId The ID of the store to associate products with.
     *                           Null if super-admin is importing for various stores (expects store_name/id in Excel).
     */
    public function __construct(int $storeId = null)
    {
        $this->user = Auth::user();
        if ($storeId) {
            $this->storeId = $storeId;
        } elseif ($this->user && !$this->user->hasRole('super-admin') && $this->user->store_id) {
            $this->storeId = $this->user->store_id;
        }
        // If super-admin and $storeId is null, we expect 'store_identifier' column in Excel.
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Determine store_id
        $currentStoreId = $this->storeId;
        if ($this->user->hasRole('super-admin') && isset($row['store_identifier'])) {
            // Super admin can specify store by name or ID in the Excel
            $store = Store::where('id', $row['store_identifier'])
                            ->orWhere('name', $row['store_identifier'])
                            ->first();
            if (!$store) {
                // Throw an error or skip this row if store not found for super-admin specified identifier
                $this->addErrorToMessages($row['name'] ?? 'Unknown Product', 'Store not found: ' . $row['store_identifier']);
                $this->skippedRowCount++;
                return null; // Skip this row
            }
            $currentStoreId = $store->id;
        }

        if (!$currentStoreId) {
             $this->addErrorToMessages($row['name'] ?? 'Unknown Product', 'Store ID could not be determined for product import.');
             $this->skippedRowCount++;
             return null; // Skip if no store context
        }

        // Find Category by name or slug within the determined store
        // Excel might have 'category_name' or 'category_slug'
        $category = Category::where('store_id', $currentStoreId)
                            ->where(function ($query) use ($row) {
                                $query->where('name', $row['category_name_or_slug'] ?? null)
                                      ->orWhere('slug', $row['category_name_or_slug'] ?? null);
                            })
                            ->first();

        if (!$category) {
            // Optionally create category if not found, or skip
            // For now, skip if category not found
            $this->addErrorToMessages($row['name'] ?? 'Unknown Product', 'Category not found: ' . ($row['category_name_or_slug'] ?? 'N/A') . ' in store ID ' . $currentStoreId);
            $this->skippedRowCount++;
            return null; // Skip this row
        }

        // Generate slug if not provided or ensure uniqueness
        $slug = isset($row['slug']) && !empty($row['slug']) ? Str::slug($row['slug']) : Str::slug($row['name']);
        $originalSlug = $slug;
        $count = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        // Check for existing product by SKU within the store to prevent duplicates
        if (!empty($row['sku'])) {
            $existingProduct = Product::where('store_id', $currentStoreId)->where('sku', $row['sku'])->first();
            if ($existingProduct) {
                // Optionally update existing product or skip
                // For now, skip if SKU already exists for this store
                 $this->addErrorToMessages($row['name'] ?? 'Unknown Product', 'Product with SKU: ' . $row['sku'] . ' already exists in store ID ' . $currentStoreId);
                 $this->skippedRowCount++;
                 return null;
            }
        }

        $this->importedRowCount++;
        return new Product([
            'store_id'        => $currentStoreId,
            'category_id'     => $category->id,
            'name'            => $row['name'],
            'slug'            => $slug,
            'description'     => $row['description'] ?? null,
            'sku'             => $row['sku'] ?? null, // Model boot method might also generate SKU
            'purchase_price'  => $row['purchase_price'] ?? 0.00,
            'sale_price'      => $row['sale_price'], // Required
            'stock_quantity'  => $row['stock_quantity'] ?? 0,
            'low_stock_threshold' => $row['low_stock_threshold'] ?? 5,
            'unit'            => $row['unit'] ?? null,
            'is_active'       => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'is_featured'     => filter_var($row['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'attributes'      => isset($row['attributes_json']) ? json_decode($row['attributes_json'], true) : null,
        ]);
    }

    /**
     * Define batch size for inserting records.
     */
    public function batchSize(): int
    {
        return 200; // Insert 200 products at a time
    }

    /**
     * Define chunk size for reading the file.
     */
    public function chunkSize(): int
    {
        return 200; // Read 200 rows at a time into memory
    }

    /**
     * Handle row validation rules.
     */
    public function rules(): array
    {
        // These rules are applied to each row BEFORE model() is called.
        // The keys must match the heading row names in your Excel/CSV.
        return [
            '*.name' => 'required|string|max:255', // Validate 'name' for each row
            '*.category_name_or_slug' => 'required|string',
            '*.sale_price' => 'required|numeric|min:0',
            '*.stock_quantity' => 'nullable|integer|min:0',
            '*.purchase_price' => 'nullable|numeric|min:0',
            '*.sku' => 'nullable|string|max:100',
            // If super-admin is importing and store_identifier is expected:
            // '*.store_identifier' => Rule::requiredIf(fn () => Auth::user()->hasRole('super-admin') && is_null($this->storeId)),
        ];
    }

    /**
     * Handle validation failures.
     * This method is part of WithValidation.
     * The SkipsOnFailure trait will call this.
     */
    public function onFailure(Failure ...$failures)
    {
        // Handle validation failures, e.g., log them or add to a list.
        // $failures is an array of Failure objects.
        // Each Failure object contains the row number, attribute, errors, and values.
        foreach ($failures as $failure) {
            $this->skippedRowCount++;
            $this->addErrorToMessages(
                'Row ' . $failure->row(),
                'Validation failed for attribute \'' . $failure->attribute() . '\': ' . implode(', ', $failure->errors()) .
                ' Given value: \'' . ($failure->values()[$failure->attribute()] ?? 'N/A') . '\''
            );
        }
    }


    /**
     * Handle errors that occur during the model creation (inside model() method).
     * This is called by SkipsOnError trait.
     */
    public function onError(Throwable $e)
    {
        // Log the error or add it to a list of errors to show to the user.
        // The $e is the actual exception caught.
        // The row data is not directly available here, so it's better to catch specific errors in model().
        $this->skippedRowCount++;
        $this->addErrorToMessages('Generic Error', 'An unexpected error occurred during import: ' . $e->getMessage() . ' at line ' . $e->getLine() . ' in ' . basename($e->getFile()));
        Log::error("ProductImport Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    }

    private function addErrorToMessages($identifier, $message)
    {
        $this->errorMessages[] = "Product/Row '{$identifier}': {$message}";
    }

    public function getImportStatus(): array
    {
        return [
            'imported' => $this->importedRowCount,
            'skipped' => $this->skippedRowCount,
            'errors' => array_slice($this->errorMessages, 0, 20) // Return first 20 errors to prevent huge response
        ];
    }
}