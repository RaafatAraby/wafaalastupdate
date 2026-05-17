<?php

namespace App\Policies;

use App\Models\Alert;
use App\Models\User;

class AlertPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->roles()->exists();
    }

    public function view(User $user, Alert $alert): bool
    {
        return $user->hasCountryAccess($alert->project?->country_id);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Alert $alert): bool
    {
        return false;
    }

    public function delete(User $user, Alert $alert): bool
    {
        return false;
    }
}
