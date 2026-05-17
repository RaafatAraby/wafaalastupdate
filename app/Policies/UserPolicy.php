<?php

namespace App\Policies;

use App\Enums\Permission as Perm;
use App\Models\User;

/**
 * Only users with the `users.manage` permission may CRUD other users.
 * (system_admin still passes via Gate::before.)
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Perm::UsersManage->value);
    }

    public function view(User $user, User $target): bool
    {
        return $user->can(Perm::UsersManage->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Perm::UsersManage->value);
    }

    public function update(User $user, User $target): bool
    {
        return $user->can(Perm::UsersManage->value);
    }

    public function delete(User $user, User $target): bool
    {
        // Self-delete is never allowed; otherwise users.manage governs.
        return $user->id !== $target->id && $user->can(Perm::UsersManage->value);
    }
}
