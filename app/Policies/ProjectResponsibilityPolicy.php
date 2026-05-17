<?php

namespace App\Policies;

use App\Enums\Permission as Perm;
use App\Models\ProjectResponsibility;
use App\Models\User;

/**
 * Project responsibility assignments — only users.manage may assign.
 */
class ProjectResponsibilityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Perm::UsersManage->value);
    }

    public function view(User $user, ProjectResponsibility $r): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Perm::UsersManage->value);
    }

    public function update(User $user, ProjectResponsibility $r): bool
    {
        return $user->can(Perm::UsersManage->value);
    }

    public function delete(User $user, ProjectResponsibility $r): bool
    {
        return false;
    }
}
