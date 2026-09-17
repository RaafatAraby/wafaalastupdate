<?php

namespace App\Console\Commands;

use App\Models\ProjectPayment;
use App\Services\ActivityLogger;
use App\Services\InternalNotifier;
use Illuminate\Console\Command;

/**
 * Daily sweep that fires payment.due_reminder / payment.overdue
 * notifications for outstanding ProjectPayment rows.
 *
 * `--days=N` extends the look-ahead window (default 0 = due today).
 * Reminders are deduplicated by toggling status pending → notified and
 * recording `notified_at`; once a payment is overdue it stays in that
 * state until marked paid or canceled.
 */
class CheckDuePaymentsCommand extends Command
{
    protected $signature = 'payments:check-due {--days=0 : Look-ahead window in days for upcoming reminders}';

    protected $description = 'Send due-date reminders for project payment schedule rows';

    public function handle(): int
    {
        $lookAhead = max(0, (int) $this->option('days'));
        $today = now()->startOfDay();
        $until = $today->copy()->addDays($lookAhead)->endOfDay();

        $dueOrUpcoming = ProjectPayment::query()
            ->with('project')
            ->whereIn('status', [ProjectPayment::STATUS_PENDING, ProjectPayment::STATUS_NOTIFIED])
            ->whereDate('due_date', '<=', $until)
            ->get();

        $reminders = 0;
        $overdue = 0;

        foreach ($dueOrUpcoming as $payment) {
            if (! $payment->project) {
                continue;
            }

            $dueDate = $payment->due_date?->startOfDay();
            if ($dueDate === null) {
                continue;
            }

            $isOverdue = $dueDate->lt($today);
            $event = $isOverdue ? 'payment.overdue' : 'payment.due_reminder';

            $amountDisplay = $payment->original_amount && $payment->currency && $payment->currency !== 'USD'
                ? number_format((float) $payment->original_amount, 2, '.', ',').' '.$payment->currency
                  .' (≈ '.number_format((float) $payment->amount, 2, '.', ',').' USD)'
                : number_format((float) $payment->amount, 2, '.', ',').' USD';

            InternalNotifier::dispatch($event, $payment, [
                'amount' => $amountDisplay,
                'ref' => $payment->project->project_number ?? (string) $payment->project_id,
                'notes' => trim(($payment->description ? $payment->description.' — ' : '')
                    .'تاريخ الاستحقاق: '.$dueDate->format('Y-m-d')),
            ]);

            ActivityLogger::log(
                $event,
                $isOverdue ? 'إشعار دفعة متأخرة' : 'تذكير بدفعة مستحقة',
                $payment,
                [
                    'due_date' => $dueDate->toDateString(),
                    'amount_usd' => (float) $payment->amount,
                ],
            );

            $payment->forceFill([
                'status' => ProjectPayment::STATUS_NOTIFIED,
                'notified_at' => now(),
            ])->save();

            $isOverdue ? $overdue++ : $reminders++;
        }

        $this->info("Payments processed: {$dueOrUpcoming->count()} (reminders: {$reminders}, overdue: {$overdue})");

        return self::SUCCESS;
    }
}
