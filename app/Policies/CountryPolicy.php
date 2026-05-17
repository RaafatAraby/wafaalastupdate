<?php

namespace App\Policies;

use App\Enums\Permission as Perm;
use App\Models\Country;
use App\Models\User;

class CountryPolicy
{
    public function viewAny(User $user): bool
    {
        // Anyone authenticated can read the country list (used in selects).
        return $user->roles()->exists();
    }

    public function view(User $user, Country $country): bool
    {
        return $user->roles()->exists();
    }

    public function create(User $user): bool
    {
        return $user->can(Perm::SettingsManage->value);
    }

    public function update(User $user, Country $country): bool
    {
        return $user->can(Perm::SettingsManage->value);
    }

    public function delete(User $user, Country $country): bool
    {
        return false;
    }
}
