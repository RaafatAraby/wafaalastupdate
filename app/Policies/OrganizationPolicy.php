<?php

namespace App\Policies;

use App\Enums\Permission as Perm;
use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->roles()->exists();
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->roles()->exists();
    }

    public function create(User $user): bool
    {
        return $user->can(Perm::SettingsManage->value);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->can(Perm::SettingsManage->value);
    }

    /**
     * Hard-delete an organization. Allowed only for مدير النظام (system_admin).
     * In practice Gate::before short-circuits true for system_admin before this
     * method runs; we return true explicitly so that if Gate::before is ever
     * tightened, the ability remains available to system_admin alone.
     */
    public function delete(User $user, Organization $organization): bool
    {
        return $user->hasRole(Role::SystemAdmin->value);
    }
}
