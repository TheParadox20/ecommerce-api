<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles & permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Permissions ───────────────────────────────────────────────────────

        $permissions = [
            // Admin-level
            'manage users',
            'manage admins',
            'manage products',
            'manage orders',
            'manage content',
            'manage settings',
            'invite distributors',

            // Buyer
            'place orders',
            'view own orders',
            'write reviews',

            // Distributor
            'view distributor stock',
            'manage distributor stock',
            'view allocated orders',

            // Influencer
            'manage vouchers',
            'view influencer stats',
            'promote products',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        // ── Roles ─────────────────────────────────────────────────────────────

        // Super Admin — gets every permission
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $superAdmin->syncPermissions(Permission::where('guard_name', 'sanctum')->get());

        // Buyer — storefront customer
        $buyer = Role::firstOrCreate(['name' => 'buyer', 'guard_name' => 'sanctum']);
        $buyer->syncPermissions([
            'place orders',
            'view own orders',
            'write reviews',
        ]);

        // Distributor — B2B partner
        $distributor = Role::firstOrCreate(['name' => 'distributor', 'guard_name' => 'sanctum']);
        $distributor->syncPermissions([
            'view distributor stock',
            'manage distributor stock',
            'view allocated orders',
            'view own orders',
        ]);

        // Influencer
        $influencer = Role::firstOrCreate(['name' => 'influencer', 'guard_name' => 'sanctum']);
        $influencer->syncPermissions([
            'view influencer stats',
            'promote products',
            'view own orders',
        ]);

        $this->command->info('✅  Roles and permissions seeded successfully.');
        $this->command->table(
            ['Role', 'Permissions'],
            [
                ['super_admin', $superAdmin->permissions->pluck('name')->implode(', ')],
                ['buyer', 'place orders, view own orders, write reviews'],
                ['distributor', 'view distributor stock, manage distributor stock, view allocated orders, view own orders'],
            ]
        );
    }
}
