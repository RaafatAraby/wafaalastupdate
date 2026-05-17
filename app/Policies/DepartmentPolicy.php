<?php

namespace App\Policies;

use App\Enums\Permission as Perm;
use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Perm::SettingsManage->value) || $user->can(Perm::UsersManage->value);
    }

    public function view(User $user, Department $department): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Perm::SettingsManage->value);
    }

    public function update(User $user, Department $department): bool
    {
        return $user->can(Perm::SettingsManage->value);
    }

    public function delete(User $user, Department $department): bool
    {
        return false;
    }
}
