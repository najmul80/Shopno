<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class RoleController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'permission:manage roles']); // Protect all methods
    }

    public function index(Request $request)
    {
        $roles = Role::query()
            ->with('permissions:id,name') // Eager load permissions (only id and name)
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->search}%");
            })
            ->where('guard_name', 'api') // Show only 'api' guard roles if you have multiple guards
            ->paginate($request->input('per_page', 15));
        return $this->successResponse($roles, 'Roles fetched successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,NULL,id,guard_name,api', // Unique role name for 'api' guard
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name,guard_name,api', // Each permission must exist for 'api' guard
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create(['name' => $validated['name'], 'guard_name' => 'api']);
            if (!empty($validated['permissions'])) {
                $permissions = Permission::whereIn('name', $validated['permissions'])->where('guard_name', 'api')->get();
                $role->syncPermissions($permissions);
            }
            DB::commit();
            return $this->successResponse($role->load('permissions:id,name'), 'Role created successfully.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Could not create role: ' . $e->getMessage(), 500);
        }
    }

    public function show(Role $role)
    {
        if ($role->guard_name !== 'api') { // Ensure it's an API guard role
            return $this->notFoundResponse('Role not found for API guard.');
        }
        return $this->successResponse($role->load('permissions:id,name'), 'Role fetched successfully.');
    }

    public function update(Request $request, Role $role)
    {
        if ($role->guard_name !== 'api') {
            return $this->notFoundResponse('Role not found for API guard.');
        }
        // Prevent updating critical roles like 'super-admin' name, or apply specific logic
        if (in_array($role->name, ['super-admin']) && $request->filled('name') && $request->name !== $role->name) {
             return $this->errorResponse("The '{$role->name}' role name cannot be changed.", 403);
        }


        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('roles', 'name')->where('guard_name', 'api')->ignore($role->id)],
            'permissions' => 'sometimes|array', // 'sometimes' means if it's present, it must be an array
            'permissions.*' => 'string|exists:permissions,name,guard_name,api',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['name'])) {
                $role->name = $validated['name'];
                $role->save();
            }
            if ($request->has('permissions')) { // Check if 'permissions' key is present in request, even if empty array
                $permissionsToSync = [];
                if (!empty($validated['permissions'])) {
                     $permissionsToSync = Permission::whereIn('name', $validated['permissions'])->where('guard_name', 'api')->get();
                }
                // Prevent removing all permissions from super-admin if that's a rule
                if ($role->name === 'super-admin' && empty($permissionsToSync) && config('permission.super_admin_role_name', 'super-admin') === 'super-admin') {
                    // If you have a config for SA role name
                    // This logic can be more complex, e.g., SA must always have certain permissions
                }

                $role->syncPermissions($permissionsToSync);
            }
            DB::commit();
            return $this->successResponse($role->fresh()->load('permissions:id,name'), 'Role updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Could not update role: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Role $role)
    {
        if ($role->guard_name !== 'api') {
            return $this->notFoundResponse('Role not found for API guard.');
        }
        // Prevent deleting critical roles
        if (in_array($role->name, ['super-admin', 'store-admin', 'sales-person'])) { // Add other essential roles
            return $this->errorResponse("The '{$role->name}' role is protected and cannot be deleted.", 403);
        }

        DB::beginTransaction();
        try {
            // Before deleting a role, you might want to re-assign users with this role to a default role,
            // or prevent deletion if users are assigned to it.
            if ($role->users()->count() > 0) {
                DB::rollBack();
                return $this->errorResponse("Cannot delete role. It is currently assigned to {$role->users()->count()} user(s).", 409); // 409 Conflict
            }
            $roleName = $role->name;
            $role->delete();
            DB::commit();
            // Clear permission cache after role/permission changes
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            return $this->successResponse(null, "Role '{$roleName}' deleted successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Could not delete role: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all available permissions (for assigning to roles in UI).
     */
    public function allPermissions()
    {
        $permissions = Permission::where('guard_name', 'api')->orderBy('name')->get(['id', 'name']);
        return $this->successResponse($permissions, 'All API permissions fetched successfully.');
    }
}