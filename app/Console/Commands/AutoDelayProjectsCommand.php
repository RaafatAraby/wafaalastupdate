<?php

namespace App\Console\Commands;

use App\Services\ProjectAutoDelay;
use Illuminate\Console\Command;

/**
 * Iterates active projects whose expected_end_date is in the past and
 * transitions them to `delayed`, sending the configured notifications.
 *
 * Intended to be wired into the scheduler:
 *   Schedule::command('projects:auto-delay')->dailyAt('00:15');
 *
 * Safe to invoke manually at any time — idempotent on already-delayed
 * projects.
 */
class AutoDelayProjectsCommand extends Command
{
    protected $signature = 'projects:auto-delay';
    protected $description = 'تحويل المشاريع التي تجاوز تاريخ انتهائها المتوقع إلى حالة متأخر';

    public function handle(): int
    {
        $count = ProjectAutoDelay::run();
        $this->info("Delayed: {$count} project(s).");
        return self::SUCCESS;
    }
}
