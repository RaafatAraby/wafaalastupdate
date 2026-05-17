<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\Attachment;
use App\Models\FinancialTransaction;
use App\Models\Project;
use App\Models\User;
use App\Observers\AttachmentObserver;
use App\Observers\FinancialTransactionObserver;
use App\Observers\ProjectObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Filament\Http\Responses\Auth\Contracts\LoginResponse::class,
            \App\Http\Responses\LoginResponse::class
        );
    }

    public function boot(): void
    {
        Project::observe(ProjectObserver::class);
        FinancialTransaction::observe(FinancialTransactionObserver::class);
        Attachment::observe(AttachmentObserver::class);

        // Super-admin shortcut: مدير النظام bypasses every policy/gate.
        // Returning null lets normal policy resolution continue for everyone else.
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole(Role::SystemAdmin->value) ? true : null;
        });
    }
}
