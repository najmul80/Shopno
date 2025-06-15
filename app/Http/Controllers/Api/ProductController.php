<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\ProductVariant; // Import ProductVariant model
use App\Models\AttributeValue; // For associating attribute values
use App\Models\ProductImage;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductImageResource;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\AddProductImageRequest;
use App\Http\Requests\Product\StoreProductVariantRequest;
use App\Http\Requests\Product\UpdateProductVariantRequest;
use App\Http\Resources\ProductVariantResource;
use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Validation\ValidationException; // For throwing validation exceptions manually
use Milon\Barcode\Facades\DNS1DFacade as DNS1D; // Barcode Facade
use Illuminate\Support\Facades\Response; // For returning image response
use SimpleSoftwareIO\QrCode\Facades\QrCode; // QR Code Facade

class ProductController extends BaseApiController
{
    protected FileStorageService $fileStorageService;

    public function __construct(FileStorageService $fileStorageService)
    {
        $this->fileStorageService = $fileStorageService;
        // $this->middleware('auth:api');
        // $this->middleware('permission:view products')->only(['index', 'show']);
        // $this->middleware('permission:create products')->only(['store']);
        // $this->middleware('permission:update products')->only(['update', 'addImages', 'deleteImage', 'setPrimaryImage', 'adjustStock', 'storeVariant', 'updateVariant', 'deleteVariant']); // Added variant methods
        // $this->middleware('permission:delete products')->only(['destroy']);
    }

    // index() and show() methods remain largely the same, but ensure 'variants.attributeValues.attribute' is eager loaded for ProductResource
    public function index(Request $request)
    {
        // Permission check is already handled by middleware in __construct
        try {
            $user = Auth::user();
            $query = Product::query();

            // Step 1: Scope the query based on user role
            // Super-admin can see all products. Others can only see products from their own store.
            if (!$user->hasRole('super-admin')) {
                $query->where('store_id', $user->store_id);
            }

            // Step 2: Eager load relationships for efficiency to avoid N+1 problem
            $query->with(['store', 'category', 'variants.attributeValues.attribute']);

            // Step 3: Add filtering capabilities
            // Search by product name, SKU, or variant SKU
            if ($request->filled('search')) {
                $searchTerm = $request->input('search');
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('sku', 'like', "%{$searchTerm}%")
                      ->orWhereHas('variants', function ($variantQuery) use ($searchTerm) {
                          $variantQuery->where('sku', 'like', "%{$searchTerm}%");
                      });
                });
            }
            
            // Filter by category
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->input('category_id'));
            }
            
            // Filter by store (only for super-admin)
            if ($request->filled('store_id') && $user->hasRole('super-admin')) {
                 $query->where('store_id', $request->input('store_id'));
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            // Step 4: Paginate the results
            $products = $query->latest()->paginate($request->input('per_page', 15));

            // Step 5: Return the paginated result through an API Resource
            // The resource collection will handle the response structure
            return ProductResource::collection($products);

        } catch (Exception $e) {
            Log::error("Error fetching products: " . $e->getMessage());
            return $this->errorResponse('Could not fetch products.', 500);
        }
    }

    // Example for show():
    public function show(Product $product)
    {
        try {
            $user = Auth::user();
            if (!$user->hasRole('super-admin') && $user->store_id !== $product->store_id) {
                return $this->forbiddenResponse('You do not have permission to view this product.');
            }
            return $this->successResponse(
                // Eager load variants and their attribute values with attributes for full display
                new ProductResource($product->loadMissing(['store', 'category', 'images', 'variants.attributeValues.attribute'])),
                'Product details fetched successfully.'
            );
        } catch (Exception $e) {
            Log::error("Error fetching product ID {$product->id}: " . $e->getMessage());
            return $this->errorResponse('Could not fetch product details.', 500);
        }
    }


    /**
     * Store a newly created product, possibly with variants.
     */
    // In app/Http/Controllers/Api/ProductController.php

public function store(StoreProductRequest $request)
{
    $currentUser = Auth::user();
    $validatedData = $request->validated();

    DB::beginTransaction();
    try {
        // ... (your existing code to get storeId and prepare productData) ...
        $storeId = ($currentUser->hasRole('super-admin') && isset($validatedData['store_id']))
            ? $validatedData['store_id']
            : $currentUser->store_id;

        if (!$storeId) {
            DB::rollBack();
            return $this->errorResponse('Store ID is required or user is not associated with a store.', 422);
        }
        $validatedData['store_id'] = $storeId;
        
        $productData = collect($validatedData)->except(['variants', 'images', 'primary_image_index'])->toArray();
        $productData['has_variants'] = !empty($validatedData['variants']);

        // Step 1: Create the product
        $product = Product::create($productData);

        // Step 2: Handle image uploads
        if ($request->hasFile('images')) {
            // ... (your existing image saving logic) ...
            foreach ($request->file('images') as $index => $imageFile) {
                $imagePath = $this->fileStorageService->store($imageFile, 'product-images/' . $product->id . '/main');
                if ($imagePath) {
                    $product->images()->create([
                        'image_path' => $imagePath,
                        'is_primary' => ($index == 0), // Let's make the first image primary by default
                        'sort_order' => $index,
                    ]);
                }
            }
        }

        // Step 3: Handle Variants
        if (!empty($validatedData['variants'])) {
            // ... (your existing variant logic) ...
        }

        DB::commit();
        activity()->causedBy($currentUser)->performedOn($product)->log('Product created: ' . $product->name);
        
        // --- THIS IS THE CRUCIAL FIX ---
        // After committing everything to the database, we "refresh" the $product object.
        // The loadMissing() method will load the 'images' relationship that was just created.
        // This ensures the ProductResource gets the complete and final data.
        $product->loadMissing('images', 'category', 'store', 'variants');

        return $this->successResponse(
            new ProductResource($product), // Now we can just pass the refreshed $product
            'Product created successfully.',
            201
        );

    } catch (Exception $e) {
        // ... (your existing catch block)
        DB::rollBack();
        Log::error('Error creating product: ' . $e->getMessage(), [
            'request_data' => $request->except(['images', 'variants.*.image_file']),
            'trace' => $e->getTraceAsString()
        ]);
        return $this->errorResponse('Could not create product: ' . $e->getMessage(), 500);
    }
}

    /**
     * Update the specified product, possibly including its variants.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $currentUser = Auth::user();
        if (!$currentUser->hasRole('super-admin') && $currentUser->store_id !== $product->store_id) {
            return $this->forbiddenResponse('You do not have permission to update this product.');
        }

        $validatedData = $request->validated();

        DB::beginTransaction();
        try {
            // Update base product data (excluding variants for now)
            $productDataToUpdate = collect($validatedData)->except(['variants'])->toArray();
            if (array_key_exists('has_variants', $validatedData)) {
                $productDataToUpdate['has_variants'] = $validatedData['has_variants'];
            } elseif (isset($validatedData['variants'])) { // Infer if variants are being managed
                $productDataToUpdate['has_variants'] = !empty($validatedData['variants']);
            }

            $product->update($productDataToUpdate);

            // Handle Variants update/create/delete (this is complex)
            // A common strategy:
            // 1. Get all existing variant IDs for this product.
            // 2. Loop through submitted variants:
            //    - If variant has an ID and it exists, update it. Remove from existing IDs list.
            //    - If variant has no ID, create it.
            // 3. Any IDs remaining in the existing IDs list are variants to be deleted.
            if ($request->has('variants')) { // Check if 'variants' key is present, even if empty array
                $submittedVariantsData = $validatedData['variants'] ?? [];
                $existingVariantIds = $product->variants()->pluck('id')->toArray();
                $processedVariantIds = [];

                foreach ($submittedVariantsData as $variantData) {
                    if (isset($variantData['id']) && in_array($variantData['id'], $existingVariantIds)) {
                        // Update existing variant
                        $variant = ProductVariant::find($variantData['id']);
                        if ($variant && $variant->product_id === $product->id) { // Ensure variant belongs to product
                            $this->createOrUpdateVariant($product, $variantData, $request, $variant);
                            $processedVariantIds[] = $variant->id;
                        }
                    } else {
                        // Create new variant
                        $newVariant = $this->createOrUpdateVariant($product, $variantData, $request);
                        $processedVariantIds[] = $newVariant->id;
                    }
                }
                // Delete variants that were not in the submitted list
                $variantsToDelete = array_diff($existingVariantIds, $processedVariantIds);
                if (!empty($variantsToDelete)) {
                    ProductVariant::whereIn('id', $variantsToDelete)->where('product_id', $product->id)->delete();
                    // Also delete their images from storage if any
                }
            }

            DB::commit();
            activity()->causedBy($currentUser)->performedOn($product)->log('Product updated: ' . $product->name);

            return $this->successResponse(
                new ProductResource($product->fresh()->loadMissing(['store', 'category', 'images', 'variants.attributeValues.attribute'])),
                'Product updated successfully.'
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Validation failed during product update.', 422, $e->errors());
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error updating product ID {$product->id}: " . $e->getMessage(), [
                'request_data' => $request->except(['variants.*.image_file']), // Sanitize
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Could not update product: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Helper method to create or update a product variant.
     * Used by store() and update() methods.
     * @param Product $product The parent product.
     * @param array $variantData Data for the variant.
     * @param Request $request The original request (for file handling).
     * @param ProductVariant|null $existingVariant If updating, pass the existing variant.
     * @return ProductVariant The created or updated variant.
     * @throws Exception
     */
    protected function createOrUpdateVariant(Product $product, array $variantData, Request $request, ProductVariant $existingVariant = null): ProductVariant
    {
        // Prepare variant data
        $data = [
            'product_id' => $product->id,
            'sku' => $variantData['sku'] ?? ($existingVariant ? $existingVariant->sku : null),
            'name_suffix' => $variantData['name_suffix'] ?? ($existingVariant ? $existingVariant->name_suffix : null),
            'additional_price' => $variantData['additional_price'] ?? ($existingVariant ? $existingVariant->additional_price : 0.00),
            'sale_price' => $variantData['sale_price'] ?? ($existingVariant ? $existingVariant->sale_price : null), // Absolute price
            'purchase_price' => $variantData['purchase_price'] ?? ($existingVariant ? $existingVariant->purchase_price : null),
            'stock_quantity' => $variantData['stock_quantity'] ?? ($existingVariant ? $existingVariant->stock_quantity : 0),
            'low_stock_threshold' => $variantData['low_stock_threshold'] ?? ($existingVariant ? $existingVariant->low_stock_threshold : null),
            'barcode' => $variantData['barcode'] ?? ($existingVariant ? $existingVariant->barcode : null),
            'is_active' => filter_var($variantData['is_active'] ?? ($existingVariant ? $existingVariant->is_active : true), FILTER_VALIDATE_BOOLEAN),
        ];

        // Handle variant-specific image upload
        // The request for image would be structured like 'variants[0][image_file]', 'variants[1][image_file]'
        // This needs careful handling of file input names and iteration if request has multiple files per variant.
        // For simplicity, assuming one image per variant, or image is handled by a separate endpoint.
        // If 'image_file' is part of $variantData and is an UploadedFile instance:
        if (isset($variantData['image_file']) && $variantData['image_file'] instanceof \Illuminate\Http\UploadedFile) {
            if ($existingVariant && $existingVariant->image_path) {
                $this->fileStorageService->delete($existingVariant->image_path);
            }
            $imagePath = $this->fileStorageService->store($variantData['image_file'], 'product-variants/' . $product->id);
            if ($imagePath) {
                $data['image_path'] = $imagePath;
            }
        } elseif (isset($variantData['image_file']) && is_null($variantData['image_file']) && $existingVariant && $existingVariant->image_path) {
            // If image_file is explicitly set to null, delete existing image
            $this->fileStorageService->delete($existingVariant->image_path);
            $data['image_path'] = null;
        }


        if ($existingVariant) {
            $existingVariant->update($data);
            $variant = $existingVariant->fresh();
        } else {
            // Generate unique SKU for new variant if not provided
            if (empty($data['sku'])) {
                $data['sku'] = strtoupper('PVAR-' . $product->id . '-' . Str::random(6));
                // Ensure this generated SKU is unique
                while (ProductVariant::where('sku', $data['sku'])->exists()) {
                    $data['sku'] = strtoupper('PVAR-' . $product->id . '-' . Str::random(6));
                }
            }
            $variant = ProductVariant::create($data);
        }

        // Sync Attribute Values
        if (isset($variantData['attribute_value_ids']) && is_array($variantData['attribute_value_ids'])) {
            // Ensure attribute values exist and belong to reasonable attributes (e.g., part of product's attribute set)
            $validAttributeValueIds = AttributeValue::whereIn('id', $variantData['attribute_value_ids'])->pluck('id');
            $variant->attributeValues()->sync($validAttributeValueIds);
        }

        return $variant;
    }


    // destroy() method remains largely the same, but ensure it handles variants if product is deleted (cascade should work)
    // addImages(), deleteImage(), setPrimaryImage(), adjustStock() methods might need adjustments
    // For example, adjustStock should now likely operate on a ProductVariant ID if product has variants.
    // The existing adjustStock is for the parent product.

    // --- Methods for managing variants of a specific product ---
    // These could also be in a separate ProductVariantController

    public function storeVariant(StoreProductVariantRequest $request, Product $product) // New FormRequest for variant
    {
        // Similar authorization as product update
        $currentUser = Auth::user();
        if (!$currentUser->hasRole('super-admin') && $currentUser->store_id !== $product->store_id) {
            return $this->forbiddenResponse('You do not have permission to add variants to this product.');
        }

        DB::beginTransaction();
        try {
            $variantData = $request->validated();
            // If 'image_file' is used in StoreProductVariantRequest, get it from request files
            if ($request->hasFile('image_file')) {
                $variantData['image_file'] = $request->file('image_file');
            }

            $variant = $this->createOrUpdateVariant($product, $variantData, $request);

            // Ensure parent product is marked as having variants
            if (!$product->has_variants) {
                $product->update(['has_variants' => true]);
            }

            DB::commit();
            activity()->causedBy($currentUser)->performedOn($variant)->log('Variant created for product: ' . $product->name);
            return $this->successResponse(
                new ProductVariantResource($variant->load('attributeValues.attribute')),
                'Product variant created successfully.',
                201
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Validation failed creating variant.', 422, $e->errors());
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error creating variant for product ID {$product->id}: " . $e->getMessage(), ['request_data' => $request->all()]);
            return $this->errorResponse('Could not create product variant: ' . $e->getMessage(), 500);
        }
    }

    public function updateVariant(UpdateProductVariantRequest $request, Product $product, ProductVariant $variant) // New FormRequest
    {
        if ($variant->product_id !== $product->id) {
            return $this->notFoundResponse('Variant does not belong to this product.');
        }
        // Similar authorization as product update
        $currentUser = Auth::user();
        if (!$currentUser->hasRole('super-admin') && $currentUser->store_id !== $product->store_id) {
            return $this->forbiddenResponse('You do not have permission to update variants for this product.');
        }

        DB::beginTransaction();
        try {
            $variantData = $request->validated();
            if ($request->hasFile('image_file')) {
                $variantData['image_file'] = $request->file('image_file');
            }

            $updatedVariant = $this->createOrUpdateVariant($product, $variantData, $request, $variant);

            DB::commit();
            activity()->causedBy($currentUser)->performedOn($updatedVariant)->log('Variant updated for product: ' . $product->name);
            return $this->successResponse(
                new ProductVariantResource($updatedVariant->load('attributeValues.attribute')),
                'Product variant updated successfully.'
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Validation failed updating variant.', 422, $e->errors());
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error updating variant ID {$variant->id} for product ID {$product->id}: " . $e->getMessage());
            return $this->errorResponse('Could not update product variant: ' . $e->getMessage(), 500);
        }
    }


    public function deleteVariant(Product $product, ProductVariant $variant)
    {
        if ($variant->product_id !== $product->id) {
            return $this->notFoundResponse('Variant does not belong to this product.');
        }
        // Similar authorization
        $currentUser = Auth::user();
        if (!$currentUser->hasRole('super-admin') && $currentUser->store_id !== $product->store_id) {
            return $this->forbiddenResponse('You do not have permission to delete variants for this product.');
        }

        DB::beginTransaction();
        try {
            if ($variant->image_path) {
                $this->fileStorageService->delete($variant->image_path);
            }
            $variantName = $variant->display_name;
            $variant->delete(); // Soft delete if ProductVariant model uses SoftDeletes

            // If this was the last variant, maybe update parent product's has_variants flag
            if (!$product->variants()->exists()) {
                $product->update(['has_variants' => false]);
            }

            DB::commit();
            activity()->causedBy($currentUser)->performedOn($product)->log("Variant '{$variantName}' deleted from product: " . $product->name);
            return $this->successResponse(null, 'Product variant deleted successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error deleting variant ID {$variant->id}: " . $e->getMessage());
            return $this->errorResponse('Could not delete product variant.', 500);
        }
    }

    // adjustStock method should now check if product has variants
    // If it has variants, stock should be adjusted at variant level, not parent product level.
    // The existing adjustStock method in ProductController is for parent product.
    // You might need a new method like `adjustVariantStock(Request $request, ProductVariant $variant)`

    /**
     * Generate and return a barcode image for a specific product variant.
     * @param Product $product The parent product (for context, if needed via route model binding).
     * @param ProductVariant $variant The product variant for which to generate the barcode.
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function getVariantBarcodeImage(Product $product, ProductVariant $variant)
    {
        // Ensure the variant belongs to the product (extra check)
        if ($variant->product_id !== $product->id) {
            return $this->notFoundResponse('Variant does not belong to the specified product.');
        }

        if (empty($variant->barcode)) {
            return $this->errorResponse('Barcode data not found for this variant.', 404);
        }

        try {
            // Generate barcode image
            // Parameters for getBarcodePNG: $code, $type, $widthFactor, $height, $colorArray
            // Common types: C39, C128, EAN13, EAN8, UPCA, UPCE, etc.
            // Choose a type suitable for your barcode data. CODE 128 is versatile.
            $barcodeType = 'C128'; // Example barcode type
            $barcodeImage = DNS1D::getBarcodePNG($variant->barcode, $barcodeType, 2, 60, [0, 0, 0], true); // widthFactor, height, color, showText=true

            return Response::make($barcodeImage, 200, ['Content-Type' => 'image/png']);
        } catch (Exception $e) {
            Log::error("Error generating barcode for variant ID {$variant->id}: " . $e->getMessage());
            return $this->errorResponse('Could not generate barcode image.', 500);
        }
    }

    /**
     * Generate and return a QR code image for a specific product.
     * @param Product $product The product for which to generate the QR code.
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function getProductQrCodeImage(Product $product, Request $request)
    {
        try {
            // Data to encode in QR code. Could be product URL, SKU, or a custom JSON string.
            // Example: Encoding product name, SKU, and a link to view it (if you have a frontend).
            $qrData = "Product: {$product->name}\nSKU: {$product->sku}\nID: {$product->id}";
            // If you have a frontend route for product details:
            // $productViewUrl = url('/products/' . $product->slug); // Example frontend URL
            // $qrData = $productViewUrl;

            $size = $request->input('size', 200); // Default QR code size 200x200
            $margin = $request->input('margin', 2); // Default margin 2

            // Generate QR code image as PNG
            $qrCodeImage = QrCode::format('png')->size($size)->margin($margin)->generate($qrData);
            // You can also generate SVG: QrCode::format('svg')->generate($qrData);

            return Response::make($qrCodeImage, 200, ['Content-Type' => 'image/png']);
        } catch (Exception $e) {
            Log::error("Error generating QR code for product ID {$product->id}: " . $e->getMessage());
            return $this->errorResponse('Could not generate QR code image.', 500);
        }
    }
}
