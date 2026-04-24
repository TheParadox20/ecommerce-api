<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;

class RefreshPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge and rebuild all roles and permissions across ALL system guards for total compatibility';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Initializing Universal Guard Reset (Total Compatibility)...");

        // 1. Clear Existing Data
        $this->warn("Purging all existing security records...");
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('roles')->truncate();
        DB::table('permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Get all guards from config
        $guards = array_keys(config('auth.guards'));
        $this->info("Targeting Guards: " . implode(', ', $guards));

        // 2. Rebuild Permissions for ALL guards
        $permissionNames = [
            'manage users',
            'manage admins',
            'manage products',
            'manage orders',
            'manage content',
            'manage settings',
            'manage vouchers',
            'invite distributors',
            'place orders',
            'view own orders',
            'write reviews',
            'view distributor stock',
            'manage distributor stock',
            'view allocated orders',
            'view influencer stats',
            'promote products',
        ];

        foreach ($guards as $guard) {
            $this->info("Forging permits for guard: [{$guard}]...");
            foreach ($permissionNames as $permName) {
                Permission::create(['name' => $permName, 'guard_name' => $guard]);
            }
        }

        // 3. Create Basic Roles for ALL guards
        $roleNames = ['super_admin', 'admin', 'distributor', 'influencer', 'buyer'];
        
        foreach ($guards as $guard) {
            $this->info("Establishing administrative tiers for guard: [{$guard}]...");
            foreach ($roleNames as $roleName) {
                $role = Role::create(['name' => $roleName, 'guard_name' => $guard]);
                
                // 4. Auto-Provision per guard
                if ($roleName === 'super_admin') {
                    $role->givePermissionTo(Permission::where('guard_name', $guard)->get());
                } elseif ($roleName === 'admin') {
                    $role->givePermissionTo(Permission::where('guard_name', $guard)->whereIn('name', [
                        'manage users', 'manage products', 'manage orders', 'manage content', 'manage vouchers', 'invite distributors'
                    ])->get());
                } elseif ($roleName === 'distributor') {
                    $role->givePermissionTo(Permission::where('guard_name', $guard)->whereIn('name', [
                        'view own orders', 'view distributor stock', 'manage distributor stock', 'view allocated orders'
                    ])->get());
                } elseif ($roleName === 'influencer') {
                    $role->givePermissionTo(Permission::where('guard_name', $guard)->whereIn('name', [
                        'view own orders', 'view influencer stats', 'promote products'
                    ])->get());
                } elseif ($roleName === 'buyer') {
                    $role->givePermissionTo(Permission::where('guard_name', $guard)->whereIn('name', [
                        'place orders', 'view own orders', 'write reviews'
                    ])->get());
                }
            }
        }

        // 5. Repair User Links (Grant roles across ALL matching guards to ensure session visibility)
        $this->info("Repairing identity associations across all system layers...");
        $roleMap = [
            'super_admin' => 'super_admin',
            'admin'       => 'admin',
            'distributor' => 'distributor',
            'influencer'  => 'influencer',
            'user'        => 'buyer',
        ];

        $repairModel = function ($model) use ($roleMap, $guards) {
            $model::all()->each(function ($instance) use ($roleMap, $guards) {
                $spatieRoleName = $roleMap[$instance->role] ?? ($instance instanceof Admin ? 'admin' : 'buyer');
                
                foreach ($guards as $guard) {
                    $roleObject = Role::where('name', $spatieRoleName)->where('guard_name', $guard)->first();
                    if ($roleObject) {
                        try {
                            $instance->assignRole($roleObject);
                        } catch (\Exception $e) {
                            // Some guards might not be supported by specific models, which is fine in a universal loop
                        }
                    }
                }
            });
        };

        $repairModel(User::class);
        $repairModel(Admin::class);

        $this->info("Security Terminal Universal Reset Complete.");
        
        return 0;
    }
}
