<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleManagementController extends Controller
{
    /**
     * List all roles with their permissions.
     */
    public function index()
    {
        // 1. Fetch ALL roles across all guards via Raw DB to avoid Spatie guard filtering
        $rawRoles = \Illuminate\Support\Facades\DB::table('roles')
            ->select('id', 'name', 'guard_name')
            ->get();

        // 2. Fetch ALL permissions from DB
        $rawPermissions = \Illuminate\Support\Facades\DB::table('permissions')
            ->select('id', 'name', 'guard_name')
            ->get();

        // 3. Mapping: Group permissions by role_id from pivot table
        $rolePermissions = \Illuminate\Support\Facades\DB::table('role_has_permissions')
            ->get()
            ->groupBy('role_id');

        // 4. Construct unified role objects for the UI
        // We group by name so the UI only shows 5 tiers even if they exist for 3 guards
        $processedRoles = $rawRoles->groupBy('name')->map(function($group, $name) use ($rawPermissions, $rolePermissions) {
            $uniqueRoleRecord = $group->first();
            
            // Collect ALL permissions linked to this role name across ANY guard
            $permIds = [];
            foreach($group as $role) {
                if (isset($rolePermissions[$role->id])) {
                    foreach($rolePermissions[$role->id] as $pivot) {
                        $permIds[] = $pivot->permission_id;
                    }
                }
            }

            // Get the unique permission names for this role grouping
            $finalPerms = $rawPermissions->whereIn('id', array_unique($permIds))
                ->map(function($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name
                    ];
                })
                ->values();

            return [
                'id' => $uniqueRoleRecord->id,
                'name' => $name,
                'permissions' => $finalPerms
            ];
        })->values();

        // 5. Unified unique permission list for the 'Forge Role' modal
        $allUniquePerms = $rawPermissions->groupBy('name')->map(function($group, $name) {
            $p = $group->first();
            return [
                'id' => $p->id,
                'name' => $name
            ];
        })->values();

        return response()->json([
            'success' => true,
            'roles' => $processedRoles,
            'all_permissions' => $allUniquePerms
        ]);
    }

    /**
     * Create a new role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        $role = Role::create(['name' => $validated['name']]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'role' => $role->load('permissions')
        ], 201);
    }

    /**
     * Sync permissions for a specific role.
     */
    public function syncPermissions(Request $request, $id)
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        $role = Role::findOrFail($id);
        
        // Prevent modification of super_admin core permissions if critical
        if ($role->name === 'super_admin') {
             // Optional: Add safety logic here
        }

        $role->syncPermissions($validated['permissions']);

        return response()->json([
            'success' => true,
            'message' => 'Permissions synchronized successfully.',
            'role' => $role->load('permissions')
        ]);
    }

    /**
     * Delete a custom role.
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        // Prevent deletion of system-critical roles
        $systemRoles = ['super_admin', 'admin', 'buyer', 'influencer', 'distributor'];
        if (in_array($role->name, $systemRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'System-critical roles cannot be deleted.'
            ], 403);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully.'
        ]);
    }
}
