<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Product permissions
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'publish_products',

            // Category permissions
            'view_categories',
            'create_categories',
            'edit_categories',
            'delete_categories',

            // Order permissions
            'view_orders',
            'create_orders',
            'edit_orders',
            'delete_orders',
            'process_payments',
            'refund_orders',

            // Customer permissions
            'view_customers',
            'create_customers',
            'edit_customers',
            'delete_customers',

            // User management permissions
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'assign_roles',

            // Report permissions
            'view_reports',
            'export_reports',

            // System permissions
            'access_admin_panel',
            'manage_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Admin role - full access
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Product Manager role
        $productManagerRole = Role::create(['name' => 'product_manager']);
        $productManagerRole->givePermissionTo([
            'access_admin_panel',
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'publish_products',
            'view_categories',
            'create_categories',
            'edit_categories',
            'delete_categories',
            'view_orders', // Can view orders related to their products
            'view_customers', // Can view customer data for product insights
        ]);

        // Sales Staff role
        $salesStaffRole = Role::create(['name' => 'sales_staff']);
        $salesStaffRole->givePermissionTo([
            'access_admin_panel',
            'view_orders',
            'create_orders',
            'edit_orders',
            'process_payments',
            'refund_orders',
            'view_customers',
            'create_customers',
            'edit_customers',
            'view_products', // Can view products to help customers
        ]);

        // Customer role - for frontend users
        $customerRole = Role::create(['name' => 'customer']);
        $customerRole->givePermissionTo([
            'view_products', // Can browse products
        ]);

        // Create super admin user if doesn't exist
        $adminUser = User::where('email', 'admin@eventticket.com')->first();
        if (! $adminUser) {
            $adminUser = User::create([
                'name' => 'Super Admin',
                'email' => 'admin@eventticket.com',
                'password' => bcrypt('password'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);
        }
        $adminUser->assignRole('admin');

        $this->command->info('Roles and permissions created successfully!');
    }
}
