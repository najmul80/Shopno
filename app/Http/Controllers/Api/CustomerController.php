<?php

namespace App\Http\Controllers\Api; // Ensure correct namespace

use App\Models\Customer;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\CustomerResource;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class CustomerController extends BaseApiController
{
    protected FileStorageService $fileStorageService;

    public function __construct(FileStorageService $fileStorageService)
    {
        $this->fileStorageService = $fileStorageService;
        $this->middleware('auth:api');
        // Add permission middleware after defining customer permissions in seeder
        $this->middleware('permission:view customers')->only(['index', 'show']);
        $this->middleware('permission:create customers')->only(['store']);
        $this->middleware('permission:update customers')->only(['update']);
        $this->middleware('permission:delete customers')->only(['destroy']);
    }

    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $query = Customer::query()->with('store');

            // Scoping for users
            if ($user->hasRole('super-admin') && $request->has('store_id_filter')) {
                $query->where('store_id', $request->store_id_filter);
            } elseif (!$user->hasRole('super-admin') && $user->store_id) {
                $query->where('store_id', $user->store_id);
            } elseif (!$user->hasRole('super-admin') && !$user->store_id) {
                return $this->forbiddenResponse('You are not assigned to a store to view customers.');
            }
            // Super-admin without filter sees all, or you can enforce filter for super-admin.

            // Further filtering
            if ($request->filled('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('email', 'like', "%{$searchTerm}%")
                      ->orWhere('phone_number', 'like', "%{$searchTerm}%");
                });
            }

            $customers = $query->latest()->paginate($request->input('per_page', 15));
            return CustomerResource::collection($customers);

        } catch (Exception $e) {
            Log::error('Error fetching customers: ' . $e->getMessage());
            return $this->errorResponse('Could not fetch customers.', 500);
        }
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(StoreCustomerRequest $request)
    {
        try {
            $user = Auth::user();
            $validatedData = $request->validated();

            // Determine store_id for the customer
            if ($user->hasRole('super-admin') && isset($validatedData['store_id'])) {
                $storeId = $validatedData['store_id'];
            } elseif (!$user->hasRole('super-admin') && $user->store_id) {
                $storeId = $user->store_id;
            } else {
                return $this->errorResponse('Store ID is required or user is not associated with a store.', 422);
            }
            $validatedData['store_id'] = $storeId;

            if ($request->hasFile('photo')) {
                $photoPath = $this->fileStorageService->store($request->file('photo'), 'customer-photos');
                if ($photoPath) {
                    $validatedData['photo_path'] = $photoPath;
                }
            }
            if(isset($validatedData['photo'])) unset($validatedData['photo']);

            $customer = Customer::create($validatedData);

            activity()->causedBy($user)->performedOn($customer)->log('Customer created: ' . $customer->name);

            return $this->successResponse(
                new CustomerResource($customer->load('store')),
                'Customer created successfully.',
                201
            );
        } catch (Exception $e) {
            Log::error('Error creating customer: ' . $e->getMessage(), ['request_data' => $request->except('photo')]);
            return $this->errorResponse('Could not create customer. ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer) // Route model binding
    {
        try {
            $user = Auth::user();
            if (!$user->hasRole('super-admin') && $user->store_id !== $customer->store_id) {
                return $this->forbiddenResponse('You do not have permission to view this customer.');
            }
            return $this->successResponse(
                new CustomerResource($customer->load('store')),
                'Customer details fetched successfully.'
            );
        } catch (Exception $e) {
            Log::error("Error fetching customer ID {$customer->id}: " . $e->getMessage());
            return $this->errorResponse('Could not fetch customer details.', 500);
        }
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        try {
            $user = Auth::user();
            if (!$user->hasRole('super-admin') && $user->store_id !== $customer->store_id) {
                return $this->forbiddenResponse('You do not have permission to update this customer.');
            }

            $validatedData = $request->validated();

            if ($request->hasFile('photo')) {
                if ($customer->photo_path) {
                    $this->fileStorageService->delete($customer->photo_path);
                }
                $photoPath = $this->fileStorageService->store($request->file('photo'), 'customer-photos');
                if ($photoPath) {
                    $validatedData['photo_path'] = $photoPath;
                }
            }
            if(isset($validatedData['photo'])) unset($validatedData['photo']);

            $customer->update($validatedData);

            activity()->causedBy($user)->performedOn($customer)->log('Customer updated: ' . $customer->name);

            return $this->successResponse(
                new CustomerResource($customer->fresh()->load('store')),
                'Customer updated successfully.'
            );
        } catch (Exception $e) {
            Log::error("Error updating customer ID {$customer->id}: " . $e->getMessage(), ['request_data' => $request->except('photo')]);
            return $this->errorResponse('Could not update customer. ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified customer from storage (soft delete).
     */
    public function destroy(Customer $customer)
    {
        try {
            $user = Auth::user();
            if (!$user->hasRole('super-admin') && $user->store_id !== $customer->store_id) {
                return $this->forbiddenResponse('You do not have permission to delete this customer.');
            }

            $customerName = $customer->name;
            $customer->delete(); // Soft delete

            activity()->causedBy($user)->log('Customer soft-deleted: ' . $customerName . ' (ID: ' . $customer->id . ')');

            return $this->successResponse(null, 'Customer soft-deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error soft-deleting customer ID {$customer->id}: " . $e->getMessage());
            return $this->errorResponse('Could not delete customer.', 500);
        }
    }
}