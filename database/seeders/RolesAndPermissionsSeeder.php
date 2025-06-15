<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Store;
// Hash facade is not strictly needed if User model's password attribute is in $casts as 'hashed'

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Define ALL Permissions based on routes/api.php
        $permissions = [
            // Dashboard
            ['name' => 'view dashboard_summary', 'guard_name' => 'api'], // from /dashboard/summary route

            // Store Management (Super Admin)
            ['name' => 'manage all_stores', 'guard_name' => 'api'], // CRUD for all stores by SA (apiResource)

            // Category Management (scoped in controller)
            ['name' => 'view categories', 'guard_name' => 'api'],
            ['name' => 'create categories', 'guard_name' => 'api'],
            ['name' => 'update categories', 'guard_name' => 'api'],
            ['name' => 'delete categories', 'guard_name' => 'api'],

            // Product Management (scoped in controller)
            ['name' => 'view products', 'guard_name' => 'api'],
            ['name' => 'create products', 'guard_name' => 'api'], // Covers variants, import
            ['name' => 'update products', 'guard_name' => 'api'], // Covers images, stock, variants
            ['name' => 'delete products', 'guard_name' => 'api'], // Covers variants
            ['name' => 'import products', 'guard_name' => 'api'], // Explicit permission for import route

            // Customer Management (scoped in controller)
            ['name' => 'view customers', 'guard_name' => 'api'],
            ['name' => 'create customers', 'guard_name' => 'api'],
            ['name' => 'update customers', 'guard_name' => 'api'],
            ['name' => 'delete customers', 'guard_name' => 'api'],

            // User Management
            ['name' => 'manage system_users', 'guard_name' => 'api'], // Super Admin: CRUD all users
            ['name' => 'manage users_own_store', 'guard_name' => 'api'], // Store Admin: CRUD users in their store

            // Sales & Invoices
            ['name' => 'process sales', 'guard_name' => 'api'],
            ['name' => 'view sales_history', 'guard_name' => 'api'], // List/view sales, view invoice JSON
            ['name' => 'download invoices', 'guard_name' => 'api'],

            // Reporting
            ['name' => 'view sales_reports_advanced', 'guard_name' => 'api'],
            ['name' => 'view stock_reports', 'guard_name' => 'api'],
            ['name' => 'view top_selling_products_report', 'guard_name' => 'api'], // from reports/top-selling-products route

            // Role & Permission Admin (Super Admin)
            ['name' => 'manage roles', 'guard_name' => 'api'],

            // Settings Admin
            ['name' => 'view global_settings', 'guard_name' => 'api'],    // New: For viewing SA settings
            ['name' => 'manage global_settings', 'guard_name' => 'api'], // Super Admin
            ['name' => 'view own_store_settings', 'guard_name' => 'api'],   // New: For viewing Store Admin settings
            ['name' => 'manage own_store_settings', 'guard_name' => 'api'],// Store Admin

            // Activity Log Admin
            ['name' => 'view activity_log', 'guard_name' => 'api'], // Super Admin
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate($permissionData);
        }
        $this->command->info(count($permissions) . ' permissions have been ensured for the "api" guard.');

        // 3. Define Roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'api']);
        $storeAdminRole = Role::firstOrCreate(['name' => 'store-admin', 'guard_name' => 'api']);
        $salesPersonRole = Role::firstOrCreate(['name' => 'sales-person', 'guard_name' => 'api']);
        $this->command->info('Core roles (super-admin, store-admin, sales-person) ensured for "api" guard.');

        // 4. Assign Permissions to Roles
        // Super Admin: Gets all defined API permissions
        $allApiPermissions = Permission::where('guard_name', 'api')->pluck('name')->all();
        $superAdminRole->syncPermissions($allApiPermissions);
        $this->command->info("Super Admin synced with " . count($allApiPermissions) . " API permissions.");

        // Store Admin: Specific set of permissions
        $storeAdminPermissions = [
            'view dashboard_summary',
            'view categories', 'create categories', 'update categories', 'delete categories',
            'view products', 'create products', 'update products', 'delete products', 'import products',
            'view customers', 'create customers', 'update customers', 'delete customers',
            'process sales', 'view sales_history', 'download invoices',
            'view sales_reports_advanced', 'view stock_reports', 'view top_selling_products_report',
            'manage users_own_store',
            'view own_store_settings', 'manage own_store_settings',
        ];
        $validStoreAdminPerms = Permission::whereIn('name', $storeAdminPermissions)->where('guard_name', 'api')->pluck('name')->all();
        $storeAdminRole->syncPermissions($validStoreAdminPerms);
        $this->command->info("Store Admin synced with " . count($validStoreAdminPerms) . " permissions.");

        // Sales Person: Specific set of permissions
        $salesPersonPermissions = [
            'view dashboard_summary',
            'view products', 'view categories',
            'create customers', 'view customers',
            'process sales',
            'view sales_history', // Controller scopes this to their own sales
        ];
        $validSalesPersonPerms = Permission::whereIn('name', $salesPersonPermissions)->where('guard_name', 'api')->pluck('name')->all();
        $salesPersonRole->syncPermissions($validSalesPersonPerms);
        $this->command->info("Sales Person synced with " . count($validSalesPersonPerms) . " permissions.");

        // 5. Create/Update Users and Assign Roles
        $superAdminUser = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            ['name' => 'Super Administrator', 'password' => 'P@$$wOrd123', 'store_id' => null, 'is_active' => true, 'email_verified_at' => now()]
        );
        if (!$superAdminUser->hasRole($superAdminRole)) {
            $superAdminUser->assignRole($superAdminRole);
        }

        $testStore = Store::firstOrCreate(
            ['name' => 'Demo Test Store'],
            [
                'email' => 'demostore@example.com',
                'city' => 'Test City',
                'is_active' => true,
                'description' => 'This is a demo store for testing purposes.'
            ]
        );

        $storeAdminUser = User::firstOrCreate(
            ['email' => 'storeadmin@example.com'],
            ['name' => 'Store Admin (Main Branch)', 'password' => 'St@reP@$$123', 'store_id' => $testStore->id, 'is_active' => true, 'email_verified_at' => now()]
        );
        if (!$storeAdminUser->hasRole($storeAdminRole)) {
            $storeAdminUser->assignRole($storeAdminRole);
        }

        $salesPersonUser = User::firstOrCreate(
            ['email' => 'salesperson@example.com'],
            ['name' => 'Sales Person (Main Branch)', 'password' => 'S@lesP@$$123', 'store_id' => $testStore->id, 'is_active' => true, 'email_verified_at' => now()]
        );
        if (!$salesPersonUser->hasRole($salesPersonRole)) {
            $salesPersonUser->assignRole($salesPersonRole);
        }

        $this->command->info('Default users (superadmin, storeadmin, salesperson) created/updated and roles assigned.');
        $this->command->warn("Default user passwords are placeholders. PLEASE CHANGE THEM.");
    }
}