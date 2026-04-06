<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Project;
use App\Observers\ProjectObserver;
use App\Models\Attachment;
use App\Models\FinancialTransaction;
use App\Observers\AttachmentObserver;
use App\Observers\FinancialTransactionObserver;



class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Project::observe(ProjectObserver::class);
        FinancialTransaction::observe(FinancialTransactionObserver::class);
Attachment::observe(AttachmentObserver::class);


    }
}
