<?php

namespace App\Models;

use App\Enums\Role as RoleEnum;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use Notifiable, HasRoles;

    protected string $guard_name = 'web';

    public function routeNotificationForWhatsapp(): ?string
    {
        return $this->whatsapp_number ? preg_replace('/\D+/', '', (string) $this->whatsapp_number) : null;
    }

    protected $fillable = [
        'name',
        'email',
        'whatsapp_number',
        'password',
        'department_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Reject inactive users and users with no roles assigned at all.
        // The latter avoids dropping a user into an empty panel.
        return (bool) $this->is_active && $this->roles()->exists();
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'user_countries');
    }

    /**
     * True for users whose role bypasses country scoping (system-wide visibility).
     *
     * Note: not named `hasGlobalScope()` because Eloquent's base Model already
     * defines a static `hasGlobalScope($scope)` for query scopes — overriding
     * it as a non-static method triggers a fatal class load error.
     */
    public function hasGlobalDataScope(): bool
    {
        // EnhancerFinanceCentral is intentionally NOT in this list: per
        // operator requirement, that role must only see data (projects,
        // financial transactions, attachments, reports) for the country/ies
        // assigned to the user via user_countries. Despite the role name
        // "central", visibility is restricted to the user's country binding.
        return $this->hasAnyRole([
            RoleEnum::SystemAdmin->value,
            RoleEnum::BoardSupervisor->value,
            RoleEnum::ProjectCreator->value,
            RoleEnum::FinalReportPreparer->value,
        ]);
    }

    /**
     * True if this user can access data scoped to the given country.
     *
     * Globally-scoped roles always pass. Country-scoped roles must have an
     * explicit row in the `user_countries` pivot.
     */
    public function hasCountryAccess(?int $countryId): bool
    {
        if (! $countryId) {
            return false;
        }

        if ($this->hasGlobalDataScope()) {
            return true;
        }

        return $this->countries()->where('countries.id', $countryId)->exists();
    }

    /** Cached list of country ids the user is allowed to see (for query scoping). */
    public function allowedCountryIds(): array
    {
        if ($this->hasGlobalDataScope()) {
            return []; // empty == "no restriction"
        }

        return $this->countries()->pluck('countries.id')->filter()->values()->all();
    }
}
