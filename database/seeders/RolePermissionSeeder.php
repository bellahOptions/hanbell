<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles and permissions.
 *
 * Three roles, deliberately coarse: `admin` runs the marketplace, `vendor`
 * manages one brand's catalogue and fulfilment, `customer` shops. Fine-grained
 * permissions exist for the areas most likely to need delegation later
 * (refunds, moderation) but the roles map to them simply for now.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Catalogue
            'products.view', 'products.create', 'products.update', 'products.delete', 'products.moderate',
            // Vendors
            'vendors.view', 'vendors.approve', 'vendors.suspend',
            // Sales
            'orders.view', 'orders.update', 'orders.refund',
            'payments.view', 'payments.refund',
            // Customers
            'customers.view', 'customers.update',
            // Content
            'pages.view', 'pages.manage', 'reviews.moderate', 'newsletter.manage',
            // Advertising
            'ads.view', 'ads.manage',
            // System
            'seo.manage', 'settings.manage', 'audit.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        /*
         * Spatie caches the permission table on first read. Because the roles are
         * synced in this same process, a cache populated before the permissions
         * existed would make syncPermissions() fail with
         * "There is no permission named ...". Flushing here is what keeps the
         * seeder re-runnable and safe to run on a fresh database.
         */
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = Role::findOrCreate('admin', 'web');
        $vendor = Role::findOrCreate('vendor', 'web');
        $customer = Role::findOrCreate('customer', 'web');

        // An administrator implicitly holds every permission. Granted explicitly
        // rather than via a Gate::before super-admin check so that the stored
        // permission list is the source of truth and auditable.
        $admin->syncPermissions($permissions);

        $vendor->syncPermissions([
            'products.view', 'products.create', 'products.update',
            'orders.view', 'orders.update',
        ]);

        $customer->syncPermissions([]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
