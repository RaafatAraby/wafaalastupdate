<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds roles and permissions from config/permissions.php.
 *
 * - Permissions not in config are removed (revoked everywhere).
 * - Roles not in config are LEFT ALONE (so manually-created roles, if any,
 *   survive). To remove a stale role, delete its model row manually.
 * - Wildcard ('*') grants every permission currently defined in config.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $matrix = Config::get('permissions', ['permissions' => [], 'roles' => []]);

        $canonicalPermissions = collect($matrix['permissions'] ?? [])->unique()->values();
        $rolesMatrix          = $matrix['roles'] ?? [];

        // 1. Sync canonical permissions: create any missing, prune any not in config.
        foreach ($canonicalPermissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        Permission::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', $canonicalPermissions->all())
            ->delete();

        // 2. Sync each role to its canonical permission set.
        foreach ($rolesMatrix as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');

            $resolved = $rolePermissions === ['*']
                ? $canonicalPermissions->all()
                : array_values(array_intersect($rolePermissions, $canonicalPermissions->all()));

            $role->syncPermissions($resolved);
        }

        // 3. Reset Spatie's permission cache so changes take effect immediately.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
