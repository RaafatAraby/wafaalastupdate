<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\FinancialTransaction;
use App\Models\Project;
use App\Models\ProjectPayment;
use App\Models\User;
use App\Notifications\InternalActionNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InternalNotifier
{
    /**
     * High-level event-driven dispatch. Looks up recipients for `$event`
     * from config/notifications-recipients.php, applies country-scoping
     * for country-scoped roles, excludes the actor, and sends a rich
     * notification (database + optional mail) carrying project context.
     */
    public static function dispatch(string $event, ?Model $subject = null, array $context = []): void
    {
        // Note: we cannot use `config("notifications-recipients.events.{$event}")`
        // because event keys (e.g. `project.created`) contain dots, which
        // Laravel's `config()` interprets as nested keys. Fetch the events
        // array first, then look up the literal key.
        $events = (array) config('notifications-recipients.events', []);
        $roles = (array) ($events[$event] ?? []);
        if (empty($roles)) {
            return;
        }

        $payload = self::buildPayload($event, $subject, $context);
        $recipients = self::recipientsFor($roles, $subject);

        foreach ($recipients as $user) {
            self::safeNotify($user, new InternalActionNotification(
                title: $payload['title'],
                body: $payload['body'],
                url: $payload['url'],
                event: $event,
                context: $payload['context'],
            ));
        }
    }

    /**
     * Send a notification without ever letting transport failures (SMTP
     * timeouts, DB write errors, WhatsApp bridge being down, …)
     * propagate back into the caller — these are typically Eloquent
     * observers that fire inside model save() and would otherwise roll
     * back the parent operation.
     */
    private static function safeNotify(User $user, $notification): void
    {
        try {
            $user->notify($notification);
        } catch (\Throwable $e) {
            Log::warning(
                'InternalNotifier: notification delivery failed',
                [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Backward-compatible role-list dispatcher. Kept so existing callers
     * keep working; internally still goes through the same notification.
     */
    public static function notifyRoles(array $roles, string $titleKey, string $bodyKey, array $replace = [], ?string $url = null): void
    {
        $title = __($titleKey, $replace);
        $body = __($bodyKey, $replace);

        // Backward-compat path doesn't have a subject, so country scoping
        // can't be applied here. Use dispatch() for that.
        $actorId = Auth::id();

        User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $roles))
            ->where('is_active', true)
            ->when($actorId, fn ($q) => $q->where('id', '!=', $actorId))
            ->each(function (User $user) use ($title, $body, $url, $titleKey, $replace) {
                // Derive a clean event slug from the translation key
                // (e.g. `notifications.project.readiness_decided.title`
                // → `project.readiness_decided`) so analytics/queries
                // on the `event` column see consistent identifiers.
                $eventSlug = preg_replace(
                    '/^notifications\\.|\\.title$|\\.body$/',
                    '',
                    $titleKey
                ) ?? $titleKey;

                self::safeNotify($user, new InternalActionNotification(
                    title: (string) $title,
                    body: (string) $body,
                    url: $url,
                    event: $eventSlug,
                    context: $replace,
                ));
            });
    }

    /**
     * Resolve users for a list of role slugs, scoping country-scoped
     * roles to the subject's country when applicable, and excluding
     * the actor and inactive users.
     */
    private static function recipientsFor(array $roles, ?Model $subject)
    {
        $countryScoped = config('notifications-recipients.country_scoped_roles', []);
        $countryId = self::countryIdFor($subject);
        $actorId = Auth::id();

        $unscopedRoles = array_values(array_diff($roles, $countryScoped));
        $scopedRoles = array_values(array_intersect($roles, $countryScoped));

        return User::query()
            ->where('is_active', true)
            ->when($actorId, fn ($q) => $q->where('id', '!=', $actorId))
            ->where(function ($outer) use ($unscopedRoles, $scopedRoles, $countryId) {
                if (! empty($unscopedRoles)) {
                    $outer->orWhereHas('roles', fn ($q) => $q->whereIn('name', $unscopedRoles));
                }

                if (! empty($scopedRoles) && $countryId !== null) {
                    $outer->orWhere(function ($scoped) use ($scopedRoles, $countryId) {
                        $scoped->whereHas('roles', fn ($q) => $q->whereIn('name', $scopedRoles))
                            ->whereHas('countries', fn ($q) => $q->where('countries.id', $countryId));
                    });
                }

                // Safety net: if we added no conditions (e.g. all roles are
                // country-scoped but the subject has no country_id), force
                // zero rows instead of letting Laravel drop the empty group
                // and silently match every active user.
                if (empty($unscopedRoles) && (empty($scopedRoles) || $countryId === null)) {
                    $outer->whereRaw('1 = 0');
                }
            })
            ->with('roles:id,name')
            ->get()
            ->unique('id');
    }

    private static function countryIdFor(?Model $subject): ?int
    {
        if ($subject === null) {
            return null;
        }

        if (isset($subject->country_id)) {
            return (int) $subject->country_id;
        }

        if ($subject instanceof Attachment || $subject instanceof FinancialTransaction || $subject instanceof ProjectPayment) {
            $project = $subject->project;
            if ($project) {
                return (int) $project->country_id;
            }
        }

        return null;
    }

    /**
     * Build the title/body strings + structured context for the
     * notification template based on the event.
     */
    private static function buildPayload(string $event, ?Model $subject, array $context): array
    {
        $project = self::projectFor($subject);
        $projectLabel = $project?->title ?? $project?->project_number ?? '-';
        $projectNumber = $project?->project_number ?? '-';

        $replace = array_merge([
            'project' => $projectLabel,
            'project_number' => $projectNumber,
            'state' => $context['state'] ?? '',
            'amount' => $context['amount'] ?? '',
            'file' => $context['file'] ?? '',
            'ref' => $context['ref'] ?? ($projectNumber ?? ''),
        ], $context);

        $titleKey = self::titleKeyFor($event);
        $bodyKey = self::bodyKeyFor($event);

        $title = __($titleKey, $replace);
        if ($title === $titleKey) {
            $title = self::fallbackTitle($event, $projectLabel);
        }

        $body = __($bodyKey, $replace);
        if ($body === $bodyKey) {
            $body = self::fallbackBody($event, $projectLabel, $context);
        }

        $url = $context['url'] ?? self::urlFor($subject);

        $stateValue = $context['state'] ?? $project?->state ?? null;

        // Subject-specific enrichment so WhatsApp / email templates can show
        // transaction date + type for financial events and category + upload
        // date for attachments without each caller having to pass them in.
        [$transactionType, $transactionTypeLabel, $transactionDate, $amountDisplay] =
            self::financialDetails($subject, $context);
        [$attachmentCategory, $attachmentCategoryLabel, $attachmentDate] =
            self::attachmentDetails($subject);

        $structuredContext = [
            'project_id' => $project?->id,
            'project_number' => $project?->project_number,
            'project_title' => $project?->title,
            'country' => optional($project?->country)->name_ar
                ?? optional($project?->country)->name_en
                ?? null,
            'state' => $stateValue,
            'state_label' => self::stateLabel($stateValue),
            'event' => $event,
            'event_label' => self::fallbackTitle($event, $projectLabel),
            'actor_name' => optional(Auth::user())->name,
            'notes' => $context['notes'] ?? null,
            'amount' => $amountDisplay ?? ($context['amount'] ?? null),
            // `file` intentionally omitted from rendered details — the user
            // does not want the random uploaded filename to appear in the
            // WhatsApp / email body. It stays available in the raw $context
            // for any consumer that explicitly needs it.
            'transaction_type' => $transactionType,
            'transaction_type_label' => $transactionTypeLabel,
            'transaction_date' => $transactionDate,
            'attachment_category' => $attachmentCategory,
            'attachment_category_label' => $attachmentCategoryLabel,
            'attachment_date' => $attachmentDate,
        ];

        return [
            'title' => (string) $title,
            'body' => (string) $body,
            'url' => $url,
            'context' => $structuredContext,
        ];
    }

    private static function projectFor(?Model $subject): ?Project
    {
        if ($subject instanceof Project) {
            return $subject;
        }

        if ($subject instanceof Attachment || $subject instanceof FinancialTransaction || $subject instanceof ProjectPayment) {
            return $subject->project;
        }

        return null;
    }

    private static function urlFor(?Model $subject): ?string
    {
        if ($subject instanceof Project) {
            return self::tryRoute('filament.admin.resources.projects.edit', $subject);
        }

        if ($subject instanceof Attachment) {
            return self::tryRoute('filament.admin.resources.attachments.edit', $subject);
        }

        if ($subject instanceof FinancialTransaction) {
            return self::tryRoute('filament.admin.resources.financial-transactions.edit', $subject);
        }

        if ($subject instanceof ProjectPayment && $subject->project) {
            // Deep-link to the parent project's edit page (the payments
            // relation manager is rendered there).
            return self::tryRoute('filament.admin.resources.projects.edit', $subject->project);
        }

        return null;
    }

    private static function tryRoute(string $name, Model $record): ?string
    {
        try {
            return route($name, ['record' => $record->getKey()]);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function titleKeyFor(string $event): string
    {
        return 'notifications.'.str_replace('.', '.', $event).'.title';
    }

    private static function bodyKeyFor(string $event): string
    {
        return 'notifications.'.str_replace('.', '.', $event).'.body';
    }

    private static function stateLabel(?string $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        return [
            'new' => 'جديد',
            'pending_readiness' => 'بانتظار اعتماد الجاهزية',
            'ready_for_execution' => 'جاهز للتنفيذ',
            'not_ready' => 'غير جاهز للتنفيذ',
            'in_execution' => 'قيد التنفيذ',
            'completed' => 'تم التنفيذ',
            'delayed' => 'متأخر',
            'final_report_pending' => 'بانتظار اعتماد التقرير',
            'final_report_approved' => 'التقرير معتمد',
            'closed' => 'مغلق',
            'archived' => 'مؤرشف',
            'pending' => 'بانتظار الاعتماد',
            'approved' => 'معتمد',
            'rejected' => 'مرفوض',
        ][$state] ?? $state;
    }

    private static function fallbackTitle(string $event, string $projectLabel): string
    {
        return match ($event) {
            'project.created' => 'إنشاء مشروع جديد',
            'project.updated' => 'تحديث بيانات مشروع',
            'project.readiness_approved' => 'اعتماد جاهزية مشروع',
            'project.readiness_rejected' => 'رفض جاهزية مشروع',
            'project.execution_updated' => 'تحديث تنفيذ مشروع',
            'project.final_report_drafted' => 'إعداد التقرير النهائي',
            'project.final_report_approved' => 'اعتماد التقرير النهائي',
            'project.rolled_back' => 'إرجاع مشروع لمرحلة سابقة',
            'project.closed' => 'إغلاق مشروع',
            'project.completed' => 'اكتمال مشروع',
            'project.archived' => 'أرشفة مشروع',
            'attachment.created' => 'إضافة مرفق جديد',
            'attachment.updated' => 'تعديل مرفق',
            'attachment.approved' => 'اعتماد مرفق',
            'attachment.rejected' => 'رفض مرفق',
            'financial.created' => 'إضافة حركة مالية',
            'financial.updated' => 'تعديل حركة مالية',
            'financial.approved' => 'اعتماد حركة مالية',
            'financial.rejected' => 'رفض حركة مالية',
            'payment.due_reminder' => 'تذكير بدفعة مستحقة',
            'payment.overdue' => 'دفعة متأخرة عن موعدها',
            default => 'تحديث في النظام',
        };
    }

    private static function fallbackBody(string $event, string $projectLabel, array $context): string
    {
        $base = match ($event) {
            'project.created' => "تم إنشاء مشروع جديد: {$projectLabel}",
            'project.readiness_approved' => "تم اعتماد جاهزية المشروع: {$projectLabel}",
            'project.readiness_rejected' => "تم رفض جاهزية المشروع: {$projectLabel}",
            'project.execution_updated' => "تم تحديث حالة تنفيذ المشروع: {$projectLabel}",
            'project.final_report_drafted' => "تم إعداد التقرير النهائي للمشروع: {$projectLabel}",
            'project.final_report_approved' => "تم اعتماد التقرير النهائي للمشروع: {$projectLabel}",
            'project.rolled_back' => "تم إرجاع المشروع: {$projectLabel} إلى مرحلة سابقة",
            'project.closed' => "تم إغلاق المشروع: {$projectLabel}",
            'project.completed' => "تم اكتمال المشروع تلقائياً: {$projectLabel}",
            'project.archived' => "تم أرشفة المشروع: {$projectLabel}",
            'attachment.created' => "تم رفع مرفق جديد للمشروع: {$projectLabel}",
            'attachment.updated' => "تم تعديل مرفق للمشروع: {$projectLabel}",
            'attachment.approved' => "تم اعتماد مرفق للمشروع: {$projectLabel}",
            'attachment.rejected' => "تم رفض مرفق للمشروع: {$projectLabel}",
            'financial.created' => "تمت إضافة حركة مالية للمشروع: {$projectLabel}",
            'financial.updated' => "تم تعديل حركة مالية للمشروع: {$projectLabel}",
            'financial.approved' => "تم اعتماد حركة مالية للمشروع: {$projectLabel}",
            'financial.rejected' => "تم رفض حركة مالية للمشروع: {$projectLabel}",
            default => "تحديث في المشروع: {$projectLabel}",
        };

        if (! empty($context['notes'])) {
            $base .= ' — ملاحظات: '.$context['notes'];
        }

        return $base;
    }

    /**
     * Pull transaction type / date / amount off a FinancialTransaction
     * subject when present, returning a 4-tuple usable by the WhatsApp +
     * email templates. All values are nullable.
     *
     * @return array{0: ?string, 1: ?string, 2: ?string, 3: ?string}
     */
    private static function financialDetails(?Model $subject, array $context): array
    {
        if (! $subject instanceof FinancialTransaction) {
            return [null, null, null, null];
        }

        $type = $subject->transaction_type;
        $typeLabel = match ($type) {
            'incoming' => 'وارد',
            'outgoing' => 'صادر',
            default => $type,
        };

        $date = $subject->transaction_date
            ? (is_string($subject->transaction_date)
                ? substr((string) $subject->transaction_date, 0, 10)
                : $subject->transaction_date->format('Y-m-d'))
            : null;

        $amountValue = $context['amount'] ?? $subject->amount;
        $amount = $amountValue !== null && $amountValue !== ''
            ? number_format((float) $amountValue, 2).' USD'
            : null;

        return [$type, $typeLabel, $date, $amount];
    }

    /**
     * Pull category / upload date off an Attachment subject. Returns a
     * 3-tuple usable by the WhatsApp + email templates. All values are
     * nullable.
     *
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private static function attachmentDetails(?Model $subject): array
    {
        if (! $subject instanceof Attachment) {
            return [null, null, null];
        }

        $category = $subject->category;
        $categoryLabel = match ($category) {
            'documentation' => 'توثيق',
            'financial' => 'مرفق مالي',
            'final_report' => 'تقرير نهائي',
            'beneficiaries_sheet' => 'كشف مستفيدين',
            'offer' => 'عرض سعر',
            'other' => 'أخرى',
            default => $category,
        };

        $date = $subject->created_at
            ? $subject->created_at->format('Y-m-d')
            : null;

        return [$category, $categoryLabel, $date];
    }
}
