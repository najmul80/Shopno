<?php

namespace App\Http\Controllers\Api; // Ensure correct namespace

use App\Models\Store; // Import Store model
use App\Http\Controllers\Api\BaseApiController; // Import BaseApiController
use App\Http\Resources\StoreResource; // Import StoreResource for formatting responses
use App\Http\Requests\Store\StoreStoreRequest; // Import FormRequest for creating stores
use App\Http\Requests\Store\UpdateStoreRequest; // Import FormRequest for updating stores
use App\Services\FileStorageService; // Import FileStorageService for logo uploads
use Illuminate\Http\Request; // Standard Request for index/show if no specific FormRequest
use Illuminate\Support\Facades\Log; // For logging
use Exception; // For catching exceptions

class StoreController extends BaseApiController // Extend BaseApiController
{
    protected FileStorageService $fileStorageService;

    public function __construct(FileStorageService $fileStorageService)
    {
        $this->fileStorageService = $fileStorageService;

        // // Apply 'auth:api' middleware to all methods in this controller.
        // // This ensures only authenticated users can access these store-related endpoints.
        // $this->middleware('auth:api');

        // // Apply permission-based middleware to specific actions.
        // // The user must have these permissions (assigned via roles) to perform the actions.
        // // Permissions are checked against the 'api' guard by default (as defined in config/permission.php and our roles/permissions seeder).
        // $this->middleware('permission:view stores')->only(['index', 'show']);
        // $this->middleware('permission:create stores')->only(['store']);
        // $this->middleware('permission:update stores')->only(['update']);
        // $this->middleware('permission:delete stores')->only(['destroy']);

        // Note: 'show' and 'update' might need more granular control if a 'store-admin'
        // should only be able to view/update THEIR OWN store. This would typically be handled
        // with a Policy or by checking ownership within the controller method.
        // For now, these permissions are general.
    }


    /**
     * Display a listing of the stores.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Authorization: For now, allow any authenticated user to see stores.
        // Later, this could be restricted by role/permission.
        // if (!auth()->user()->can('view stores_any')) {
        //     return $this->forbiddenResponse();
        // }

        try {
            $stores = Store::query()
                ->when($request->input('is_active'), function ($query, $isActive) {
                    return $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
                })
                ->when($request->input('search'), function ($query, $searchTerm) {
                    return $query->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhere('city', 'like', "%{$searchTerm}%");
                })
                ->latest() // Order by latest created
                ->paginate($request->input('per_page', 15)); // Paginate results

            return StoreResource::collection($stores); // Use collection for paginated results
        } catch (Exception $e) {
            Log::error('Error fetching stores: ' . $e->getMessage());
            return $this->errorResponse('Could not fetch stores.', 500);
        }
    }

    /**
     * Store a newly created store in storage.
     * @param  \App\Http\Requests\Store\StoreStoreRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreStoreRequest $request)
    {
        // Authorization is handled by StoreStoreRequest's authorize() method.
        // If authorize() returns false, a 403 response is automatically sent.

        try {
            $validatedData = $request->validated();

            if ($request->hasFile('logo')) {
                $logoPath = $this->fileStorageService->store($request->file('logo'), 'store-logos');
                if ($logoPath) {
                    $validatedData['logo_path'] = $logoPath;
                } else {
                    // Log warning, but proceed without logo if storage failed
                    Log::warning('Store logo upload failed during store creation.');
                }
            }
            // Remove 'logo' from validatedData as it's a file object, not a db column for store itself
            if (isset($validatedData['logo'])) {
                unset($validatedData['logo']);
            }


            $store = Store::create($validatedData);

            activity()->causedBy(auth()->user())->performedOn($store)->log('Store created: ' . $store->name);

            return $this->successResponse(
                new StoreResource($store),
                'Store created successfully.',
                201 // HTTP 201 Created
            );
        } catch (Exception $e) {
            Log::error('Error creating store: ' . $e->getMessage(), ['request_data' => $request->except('logo')]);
            return $this->errorResponse('Could not create store. ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified store.
     * @param  \App\Models\Store  $store (Route Model Binding)
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Store $store)
    {
        // Authorization: Check if user can view this specific store
        // Example: if (!auth()->user()->can('view stores_own', $store) && !auth()->user()->can('view stores_any')) {
        //     return $this->forbiddenResponse();
        // }

        try {
            return $this->successResponse(
                new StoreResource($store),
                'Store details fetched successfully.'
            );
        } catch (Exception $e) {
            // This catch block might not be reached if route model binding fails (404 is sent by Laravel).
            // But good to have for other unexpected errors.
            Log::error("Error fetching store details for ID {$store->id}: " . $e->getMessage());
            return $this->errorResponse('Could not fetch store details.', 500);
        }
    }

    /**
     * Update the specified store in storage.
     * @param  \App\Http\Requests\Store\UpdateStoreRequest  $request
     * @param  \App\Models\Store  $store (Route Model Binding)
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateStoreRequest $request, Store $store)
    {
        // Authorization is handled by UpdateStoreRequest's authorize() method.

        try {
            $validatedData = $request->validated();

            if ($request->hasFile('logo')) {
                // Delete old logo if it exists
                if ($store->logo_path) {
                    $this->fileStorageService->delete($store->logo_path);
                }
                // Store the new logo
                $logoPath = $this->fileStorageService->store($request->file('logo'), 'store-logos');
                if ($logoPath) {
                    $validatedData['logo_path'] = $logoPath;
                } else {
                    Log::warning("Store logo upload failed during store update for store ID: {$store->id}.");
                    // Potentially unset 'logo_path' if upload failed to prevent storing old/invalid path
                    unset($validatedData['logo_path']);
                }
            }
            // Remove 'logo' from validatedData as it's a file object
            if (isset($validatedData['logo'])) {
                unset($validatedData['logo']);
            }

            $store->update($validatedData);

            activity()->causedBy(auth()->user())->performedOn($store)->log('Store updated: ' . $store->name);

            return $this->successResponse(
                new StoreResource($store->fresh()), // Return the updated store data
                'Store updated successfully.'
            );
        } catch (Exception $e) {
            Log::error("Error updating store ID {$store->id}: " . $e->getMessage(), ['request_data' => $request->except('logo')]);
            return $this->errorResponse('Could not update store. ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified store from storage (soft delete).
     * @param  \App\Models\Store  $store (Route Model Binding)
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Store $store)
    {
        // Authorization: Check if user can delete this store
        // Example: if (!auth()->user()->can('delete stores')) { // Or specific permission for this store
        //    return $this->forbiddenResponse();
        // }
        // For now, let's assume the route middleware handles role check (e.g. super-admin)

        try {
            // Check if there are dependent records (e.g., users, products) before deleting,
            // if onDelete constraint is 'restrict'. Soft delete bypasses some of these DB-level checks.
            // if ($store->users()->count() > 0 || $store->products()->count() > 0) {
            //    return $this->errorResponse('Cannot delete store. It has associated users or products.', 409); // 409 Conflict
            // }

            $storeName = $store->name; // Get name before deleting for logging
            $store->delete(); // Soft delete

            activity()->causedBy(auth()->user())->log('Store soft-deleted: ' . $storeName . ' (ID: ' . $store->id . ')');


            return $this->successResponse(
                null, // No data to return on successful deletion
                'Store soft-deleted successfully.'
            );
        } catch (Exception $e) {
            Log::error("Error soft-deleting store ID {$store->id}: " . $e->getMessage());
            return $this->errorResponse('Could not delete store.', 500);
        }
    }

    // You might add methods for forceDelete or restore if using soft deletes extensively
}
