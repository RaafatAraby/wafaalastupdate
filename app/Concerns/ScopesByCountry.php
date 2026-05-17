<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Centralised "limit to user's countries" query scope.
 *
 * Apply this trait to a model that has a `country_id` column (or a
 * `project` relation whose project owns the country) and call
 * `Model::query()->visibleTo($user)` from any Resource.
 */
trait ScopesByCountry
{
    /**
     * Configurable hooks: override in models if defaults don't fit.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasGlobalDataScope()) {
            return $query;
        }

        $countryIds = $user->allowedCountryIds();

        if (empty($countryIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $this->applyCountryFilter($query, $countryIds);
    }

    /**
     * Override this in models that need to filter through a relation
     * (e.g. FinancialTransaction filters via its project).
     */
    protected function applyCountryFilter(Builder $query, array $countryIds): Builder
    {
        return $query->whereIn($this->getCountryForeignKeyColumn(), $countryIds);
    }

    protected function getCountryForeignKeyColumn(): string
    {
        return 'country_id';
    }
}
