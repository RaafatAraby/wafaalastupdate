<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily 07:00 (Asia/Riyadh) sweep that fires payment due / overdue
// reminders. Look-ahead window = 3 days so payments coming up later this
// week still get an early heads-up; the same payment will only be
// notified once because the command flips its status to `notified`.
Schedule::command('payments:check-due', ['--days=3'])
    ->dailyAt('07:00')
    ->timezone('Asia/Riyadh')
    ->withoutOverlapping()
    ->onOneServer();
