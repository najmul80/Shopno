<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Store;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\UserResource;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Services\FileStorageService;
use Illuminate\Http\Request; // Request for index method
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class UserController extends BaseApiController
{
    protected FileStorageService $fileStorageService;

    public function __construct(FileStorageService $fileStorageService)
    {
        $this->fileStorageService = $fileStorageService;
        $this->middleware('auth:api');
        // Permission middleware is applied on routes
    }

    // index() method remains as it was (using Request $request)
    public function index(Request $request)
    {
        // ... (your existing index method code) ...
        // This method should work fine as it doesn't rely on route model binding for a single user
        $currentUser = Auth::user();
        if (!$currentUser->hasPermissionTo('manage system_users', 'api') && !$currentUser->hasPermissionTo('manage users_own_store', 'api')) {
             return $this->forbiddenResponse('You do not have permission to list users.');
        }
        // ... (rest of your index logic) ...
        try {
            $query = User::query()->with(['store', 'roles']);

            if ($currentUser->hasPermissionTo('manage system_users', 'api')) {
                if ($request->filled('store_id_filter')) {
                    $query->where('store_id', $request->store_id_filter);
                }
                if ($request->filled('role_filter')) {
                    $roleName = $request->role_filter;
                    $query->whereHas('roles', function (Builder $q) use ($roleName) {
                        $q->where('name', $roleName);
                    });
                }
                // Add is_active filter if you have it in User model and request
                if ($request->filled('is_active_filter')) {
                     $query->where('is_active', filter_var($request->is_active_filter, FILTER_VALIDATE_BOOLEAN));
                }

            } elseif ($currentUser->hasPermissionTo('manage users_own_store', 'api') && $currentUser->store_id) {
                $query->where('store_id', $currentUser->store_id)
                      ->whereDoesntHave('roles', function (Builder $q) {
                            $q->whereIn('name', ['super-admin', 'store-admin']);
                      }, 'and', function(Builder $q) use ($currentUser) {
                            $q->where('users.id', '!=', $currentUser->id);
                      });
                 if ($request->filled('role_filter') && !in_array($request->role_filter, ['super-admin', 'store-admin'])) {
                     $roleName = $request->role_filter;
                     $query->whereHas('roles', function (Builder $q) use ($roleName) {
                         $q->where('name', $roleName);
                     });
                 }
            } else {
                return $this->forbiddenResponse('You do not have adequate permissions to list users.');
            }

            if ($request->filled('search')) {
                 $query->where(function (Builder $q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%");
                });
            }

            $users = $query->latest()->paginate($request->input('per_page', 15));
            return UserResource::collection($users);

        } catch (Exception $e) {
            Log::error('Error fetching users: ' . $e->getMessage(), ['user_id' => $currentUser->id ?? null]);
            return $this->errorResponse('Could not fetch users.', 500);
        }
    }


    // store() method remains as it was (using StoreUserRequest $request)
    public function store(StoreUserRequest $request)
    {
        $currentUser = Auth::user(); // The user performing the creation action
        Log::info("UserController@store: Attempting to create new user by User ID [{$currentUser->id} - {$currentUser->email}]");

        // Although route middleware handles broad permission, specific internal checks can be useful.
        // This check ensures the user has AT LEAST one of the required permissions to even attempt.
        // The route middleware 'permission:manage system_users|manage users_own_store' already does this.
        // So, this internal check might be redundant if route middleware is correctly set.
        // However, if you want to be extra safe or have different logic not covered by simple OR in middleware:
        $canCreateAsSystem = $currentUser->hasPermissionTo('manage system_users', 'api');
        $canCreateForOwnStore = $currentUser->hasPermissionTo('manage users_own_store', 'api');

        if (!$canCreateAsSystem && !$canCreateForOwnStore) {
            Log::warning("UserController@store: Authorization failed for User ID [{$currentUser->id}]. Missing create permissions.");
            return $this->forbiddenResponse('You do not have permission to create users.');
        }

        $validatedData = $request->validated(); // Get data after FormRequest validation
        Log::info('UserController@store: Validated Data received: ', $validatedData);

        $assignedRolesInput = $validatedData['roles']; // Array of role names from request

        DB::beginTransaction(); // Start database transaction
        Log::info('UserController@store: DB Transaction Started for new user creation.');

        try {
            $storeIdForNewUser = null;
            $isNewUserSuperAdmin = in_array('super-admin', $assignedRolesInput);

            // --- Determine store_id and validate role assignment permissions ---
            if ($canCreateAsSystem) { // Current user is effectively a Super Admin for user creation
                Log::info("UserController@store: Creator [{$currentUser->id}] has 'manage system_users' permission.");
                if ($isNewUserSuperAdmin) {
                    $storeIdForNewUser = null; // Super Admins are not tied to a store
                    Log::info("UserController@store: New user will be a Super Admin, store_id set to null.");
                } else {
                    // For non-super-admin roles, SA can specify a store_id or it might be optional
                    // depending on whether the role itself is store-specific.
                    $storeIdForNewUser = $validatedData['store_id'] ?? null; // store_id from request
                    if (is_null($storeIdForNewUser) && count(array_intersect($assignedRolesInput, ['store-admin', 'store-manager', 'sales-person'])) > 0) {
                        // If assigning a store-specific role but no store_id provided
                        Log::warning('UserController@store: Super Admin creating store-specific role user without store_id.');
                        DB::rollBack();
                        // This validation is also in StoreUserRequest, but double-checking is fine.
                        throw ValidationException::withMessages(['store_id' => 'A store assignment is required for store-specific roles when creating as Super Admin.']);
                    }
                    Log::info("UserController@store: Super Admin creating non-SA user for store_id: [{$storeIdForNewUser}].");
                }
            } elseif ($canCreateForOwnStore && $currentUser->store_id) { // Current user is a Store Admin
                Log::info("UserController@store: Creator [{$currentUser->id}] has 'manage users_own_store' permission for store_id [{$currentUser->store_id}].");
                $storeIdForNewUser = $currentUser->store_id; // User created will belong to current Store Admin's store

                // Store admin should only be able to assign non-administrative roles within their store
                $allowedRolesForStoreAdminToAssign = config('permission.store_admin_assignable_roles', ['sales-person', 'store-manager']);
                foreach ($assignedRolesInput as $roleName) {
                    if (!in_array($roleName, $allowedRolesForStoreAdminToAssign)) {
                        Log::warning("UserController@store: Store Admin [{$currentUser->id}] attempted to assign unauthorized role '{$roleName}'.");
                        DB::rollBack();
                        return $this->forbiddenResponse("You are not authorized to assign the '{$roleName}' role.");
                    }
                }
                Log::info("UserController@store: Store Admin creating user with roles: [" . implode(', ', $assignedRolesInput) . "] for their store_id: [{$storeIdForNewUser}].");
            } else {
                // This case should ideally be caught by initial permission check or route middleware
                Log::warning("UserController@store: Creator [{$currentUser->id}] lacks specific context (e.g., store_id for store admin) or permissions for user creation.");
                DB::rollBack();
                return $this->forbiddenResponse('You do not have the necessary permissions or store context to create this user.');
            }

            // --- Prepare User Data for Creation ---
            $userData = [
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => $validatedData['password'], // Already validated (confirmed) and will be hashed by User model
                'store_id' => $storeIdForNewUser,
                // Add 'is_active' if it's part of your User model and request
                // 'is_active' => $validatedData['is_active'] ?? true,
            ];

            // Handle profile photo upload if present
            if ($request->hasFile('profile_photo') && $request->file('profile_photo')->isValid()) {
                Log::info('UserController@store: Profile photo found and is valid.');
                $path = $this->fileStorageService->store($request->file('profile_photo'), 'user-profiles');
                if ($path) {
                    $userData['profile_photo_path'] = $path;
                    Log::info('UserController@store: Profile photo stored at path: ' . $path);
                } else {
                    Log::warning('UserController@store: Profile photo storage failed for new user.');
                    // Decide if this is a critical failure. For now, proceed without photo.
                }
            }

            Log::info('UserController@store: Preparing to create user with data: ', array_diff_key($userData, array_flip(['password']))); // Log data without password

            // --- Create User and Assign Roles ---
            $newUser = User::create($userData);
            Log::info("UserController@store: New user successfully created with ID: [{$newUser->id}], Email: [{$newUser->email}].");

            // Verify that the roles exist for the 'api' guard before assigning
            $rolesToAssign = Role::whereIn('name', $assignedRolesInput)
                                ->where('guard_name', 'api') // Crucial for multi-guard setups
                                ->get();

            if ($rolesToAssign->count() !== count($assignedRolesInput)) {
                $missingRoles = array_diff($assignedRolesInput, $rolesToAssign->pluck('name')->all());
                Log::warning('UserController@store: One or more specified roles do not exist or are not for API guard.', ['requested_roles' => $assignedRolesInput, 'found_roles_for_api_guard' => $rolesToAssign->pluck('name')->all(), 'missing_or_wrong_guard_roles' => $missingRoles]);
                DB::rollBack();
                return $this->errorResponse('One or more specified roles are invalid for the API guard. Missing: ' . implode(', ',$missingRoles), 422);
            }

            $newUser->assignRole($rolesToAssign->pluck('name')->all()); // Assign by role names
            Log::info("UserController@store: Roles [" . implode(', ', $rolesToAssign->pluck('name')->all()) . "] assigned to new user ID [{$newUser->id}].");

            DB::commit(); // All operations successful, commit transaction
            Log::info("UserController@store: DB Transaction Committed for new user ID [{$newUser->id}].");

            // Log activity after successful commit
            activity()->causedBy($currentUser)->performedOn($newUser)
                      ->withProperties(['assigned_roles' => $newUser->getRoleNames()->toArray(), 'store_id' => $newUser->store_id])
                      ->log('User created via admin panel: ' . $newUser->email);
            Log::info("UserController@store: Activity logged for new user ID [{$newUser->id}].");

            // Send notification to other Super Admins about new user creation by an admin
            // (Similar to AuthController@register, but here causer is $currentUser)
            try {
                $superAdminsToNotify = User::whereHas('roles', fn($q) => $q->where('name', 'super-admin')->where('guard_name', 'api'))
                                          ->where('id', '!=', $currentUser->id) // Don't notify the creator if they are SA
                                          ->get();
                if($superAdminsToNotify->isNotEmpty()){
                    // You might want a different notification class, e.g., AdminCreatesUserNotification
                    // For now, reusing NewUserRegisteredNotification.
                    foreach($superAdminsToNotify as $sa){
                        $sa->notifyNow(new \App\Notifications\Auth\NewUserRegisteredNotification($newUser)); // Pass the newly created user
                    }
                     Log::info("UserController@store: Notification sent to other super admins about user created by User ID [{$currentUser->id}].");
                }
            } catch(Exception $eN){
                Log::error("UserController@store: Failed to send notification about admin-created user.", ['error' => $eN->getMessage()]);
            }


            return $this->successResponse(
                new UserResource($newUser->fresh()->loadMissing(['store', 'roles'])), // Use fresh() to get latest data and load relations
                'User created successfully by admin.',
                201 // HTTP 201 Created
            );

        } catch (ValidationException $e) { // Catch specific ValidationException if thrown manually
            DB::rollBack();
            Log::warning('UserController@store: ValidationException during user creation by admin.', [
                'errors' => $e->errors(),
                'message' => $e->getMessage(),
                'request_data' => $request->safe()->except(['password', 'password_confirmation', 'profile_photo'])
            ]);
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
        catch (Exception $e) { // Catch any other general exceptions
            DB::rollBack();
            Log::error('UserController@store: General exception during user creation by admin.', [
                'error_message' => $e->getMessage(),
                'trace_snippet' => substr($e->getTraceAsString(), 0, 1000),
                'request_data' => $request->safe()->except(['password', 'password_confirmation', 'profile_photo'])
            ]);
            return $this->errorResponse('Could not create user due to a server error. ' . $e->getMessage(), 500);
        }
    }


    /**
     * Display the specified user.
     * Manually fetches user by ID.
     *
     * @param  int|string  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($userId) // Changed: Accept $userId instead of User $userToView
    {
        Log::info("UserController@show (Manual Fetch) called for User ID: {$userId}");
        $userToView = User::find($userId); // Manually find the user

        if (!$userToView) {
            Log::warning("UserController@show (Manual Fetch): User with ID [{$userId}] not found.");
            return $this->notFoundResponse('User not found.');
        }

        $currentUser = Auth::user();
        try {
            // Authorization Logic (using the manually fetched $userToView)
            $canView = false;
            if ($currentUser->hasPermissionTo('manage system_users', 'api')) {
                $canView = true;
            } elseif (
                $currentUser->hasPermissionTo('manage users_own_store', 'api') &&
                $currentUser->store_id &&
                $userToView->store_id === $currentUser->store_id
            ) {
                if ($userToView->hasAnyRole(['super-admin', 'store-admin'], 'api') && $userToView->id !== $currentUser->id) {
                    return $this->forbiddenResponse('You cannot view details of this user type within your store.');
                }
                $canView = true;
            }

            if (!$canView) {
                return $this->forbiddenResponse('You do not have permission to view this user.');
            }

            return $this->successResponse(
                new UserResource($userToView->loadMissing(['store', 'roles'])),
                'User details fetched successfully.'
            );
        } catch (Exception $e) {
            Log::error("Error in UserController@show (Manual Fetch) for user ID {$userId}: " . $e->getMessage(), ['trace' => substr($e->getTraceAsString(),0,500)]);
            return $this->errorResponse('Could not fetch user details.', 500);
        }
    }

    /**
     * Update the specified user.
     * Uses UpdateUserRequest for validation.
     * Manually fetches user by ID.
     *
     * @param  \App\Http\Requests\User\UpdateUserRequest  $request
     * @param  int|string  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateUserRequest $request, $userId) // Changed: Accept $userId
    {
        Log::info("UserController@update (Manual Fetch) called for User ID: {$userId}");
        $userToUpdate = User::find($userId);

        if (!$userToUpdate) {
            Log::warning("UserController@update (Manual Fetch): User with ID [{$userId}] not found.");
            return $this->notFoundResponse('User not found to update.');
        }

        $currentUser = Auth::user();
        $validatedData = $request->validated();
        DB::beginTransaction();
        try {
            // ... (Your existing authorization logic from previous `update` method, using $userToUpdate) ...
            $canManageSystemUsers = $currentUser->hasPermissionTo('manage system_users', 'api');
            $canManageOwnStoreUsers = $currentUser->hasPermissionTo('manage users_own_store', 'api') && $currentUser->store_id && ($userToUpdate->store_id === $currentUser->store_id);

            if (!$canManageSystemUsers && !$canManageOwnStoreUsers) {
                DB::rollBack();
                return $this->forbiddenResponse('You do not have permission to update this user.');
            }
            // ... (rest of your authorization and update logic from previous `update` method using $userToUpdate) ...
            // Example snippet of update logic
            $updateData = [];
            if (isset($validatedData['name'])) $updateData['name'] = $validatedData['name'];
            // ... (prepare other $updateData fields) ...
            if ($request->hasFile('profile_photo')) {
                // ... (file handling logic) ...
                 if ($userToUpdate->profile_photo_path) {
                    $this->fileStorageService->delete($userToUpdate->profile_photo_path);
                }
                $path = $this->fileStorageService->store($request->file('profile_photo'), 'user-profiles');
                if ($path) $updateData['profile_photo_path'] = $path;
            }
            $userToUpdate->update($updateData);
            if (isset($validatedData['roles'])) {
                // ... (role syncing logic) ...
                 $validRoles = Role::whereIn('name', $validatedData['roles'])->where('guard_name', 'api')->pluck('name')->all();
                if (count($validRoles) !== count($validatedData['roles'])) {
                    DB::rollBack();
                    return $this->errorResponse('One or more specified roles for update are invalid.', 422);
                }
                if (in_array('super-admin', $validRoles) && !is_null($userToUpdate->store_id)) {
                    $userToUpdate->update(['store_id' => null]);
                }
                $userToUpdate->syncRoles($validRoles);
            }


            DB::commit();
            activity()->causedBy($currentUser)->performedOn($userToUpdate)->log('User updated via admin: ' . $userToUpdate->email);
            return $this->successResponse(new UserResource($userToUpdate->fresh()->load(['store', 'roles'])), 'User updated successfully.');

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error updating user ID {$userId} by admin (Manual Fetch): " . $e->getMessage(), ['request_data' => $request->except(['password', 'password_confirmation', 'profile_photo']), 'trace' => substr($e->getTraceAsString(),0,500)]);
            return $this->errorResponse('Could not update user. ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified user from storage.
     * Manually fetches user by ID.
     *
     * @param  int|string  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($userId) // Changed: Accept $userId
    {
        Log::info("UserController@destroy (Manual Fetch) called for User ID: {$userId}");
        $userToDelete = User::find($userId);

        if (!$userToDelete) {
            Log::warning("UserController@destroy (Manual Fetch): User with ID [{$userId}] not found.");
            return $this->notFoundResponse('User not found to delete.');
        }

        $currentUser = Auth::user();
        DB::beginTransaction();
        try {
            // ... (Your existing authorization logic from previous `destroy` method, using $userToDelete) ...
            if ($userToDelete->id === $currentUser->id) { /* ... cannot delete self ... */ }
            // ... (other authorization checks) ...
            $canManageSystemUsers = $currentUser->hasPermissionTo('manage system_users', 'api');
            $canManageOwnStoreUsers = $currentUser->hasPermissionTo('manage users_own_store', 'api') && $currentUser->store_id && ($userToDelete->store_id === $currentUser->store_id);

            if (!$canManageSystemUsers && !$canManageOwnStoreUsers) {
                 DB::rollBack();
                return $this->forbiddenResponse('You do not have permission to delete this user.');
            }
             if ($canManageOwnStoreUsers && !$canManageSystemUsers) { // Store admin specific restriction
                if ($userToDelete->hasAnyRole(['super-admin', 'store-admin'])) {
                    DB::rollBack();
                    return $this->forbiddenResponse('You cannot delete an administrator account from your store.');
                }
            }


            if ($userToDelete->profile_photo_path) {
                $this->fileStorageService->delete($userToDelete->profile_photo_path);
            }

            $userEmail = $userToDelete->email;
            $uId = $userToDelete->id; // Store before delete if User model uses SoftDeletes
            $userToDelete->delete();

            DB::commit();
            activity()->causedBy($currentUser)->log('User deleted via admin: ' . $userEmail . ' (ID: ' . $uId . ')');
            return $this->successResponse(null, 'User deleted successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error deleting user ID {$userId} by admin (Manual Fetch): " . $e->getMessage(), ['trace' => substr($e->getTraceAsString(),0,500)]);
            return $this->errorResponse('Could not delete user.', 500);
        }
    }
}