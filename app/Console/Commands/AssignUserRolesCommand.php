<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Country;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

/**
 * Interactive command to inspect users, assign Spatie roles, and bind
 * country-scoped roles to specific countries.
 *
 * Usage on the server:
 *
 *   php artisan users:assign-roles
 *
 * The command lists every user with their current role(s) and country
 * bindings, then walks the operator through a guided assignment flow with
 * confirmations — no tinker, no manual queries.
 */
class AssignUserRolesCommand extends Command
{
    protected $signature = 'users:assign-roles
                            {--all : Walk through every user one by one}
                            {--user= : Operate on a single user (id or email)}';

    protected $description = 'List users and interactively assign roles + country bindings.';

    /** @var array<int, string> */
    private array $countryScopedRoles = [
        'readiness_approver',
        'project_executor',
        'enhancer_entry_country',
        'enhancer_finance_central',
    ];

    public function handle(): int
    {
        $this->components->info('أداة إسناد الأدوار للمستخدمين — Wafaa');
        $this->showCurrentState();

        $userOption = $this->option('user');

        if ($userOption !== null) {
            $user = $this->resolveUser($userOption);
            if ($user === null) {
                $this->components->error("لم يتم العثور على المستخدم: {$userOption}");

                return self::FAILURE;
            }
            $this->assignFor($user);

            return self::SUCCESS;
        }

        if ($this->option('all')) {
            User::query()->orderBy('id')->get()->each(fn (User $user) => $this->assignFor($user));

            return self::SUCCESS;
        }

        // Default: pick a user from a list.
        $userId = select(
            label: 'اختر المستخدم لتعديل دوره',
            options: User::query()
                ->orderBy('id')
                ->get()
                ->mapWithKeys(fn (User $u) => [
                    (string) $u->id => sprintf('#%d — %s <%s>', $u->id, $u->name, $u->email),
                ])
                ->all(),
            scroll: 15,
        );

        $this->assignFor(User::findOrFail((int) $userId));

        return self::SUCCESS;
    }

    private function showCurrentState(): void
    {
        $rows = User::query()
            ->with(['roles:id,name', 'countries:id,name_ar,name_en'])
            ->orderBy('id')
            ->get()
            ->map(function (User $u) {
                $roles = $u->roles->pluck('name')->implode(', ') ?: '—';
                $countries = $u->countries->map(fn (Country $c) => $this->countryLabel($c))->implode(', ') ?: '—';
                $legacy = $this->legacyRoleFor($u->id) ?? '—';

                return [
                    $u->id,
                    $u->name,
                    $u->email,
                    $u->is_active ? 'نعم' : 'لا',
                    $roles,
                    $countries,
                    $legacy,
                ];
            })
            ->all();

        $this->table(
            ['#', 'الاسم', 'البريد', 'مفعَّل', 'الأدوار الحالية', 'الدول', 'الدور القديم'],
            $rows,
        );
    }

    private function assignFor(User $user): void
    {
        $this->newLine();
        $this->components->info(sprintf('تعديل المستخدم #%d — %s <%s>', $user->id, $user->name, $user->email));

        $current = $user->roles->pluck('name')->all();
        if ($current !== []) {
            $this->line('الأدوار الحالية: ' . implode(', ', $current));
        }
        $legacy = $this->legacyRoleFor($user->id);
        if ($legacy !== null) {
            $this->line("الدور القديم في النظام السابق: {$legacy}");
        }

        $skip = ! confirm(
            label: 'هل تريد تعديل دور هذا المستخدم؟',
            default: $current === [],
        );
        if ($skip) {
            return;
        }

        $newRole = select(
            label: 'اختر الدور الجديد',
            options: $this->roleOptions(),
            scroll: 10,
        );

        $user->syncRoles([$newRole]);
        $this->components->info("تم إسناد الدور: {$newRole}");

        if (in_array($newRole, $this->countryScopedRoles, true)) {
            $this->bindCountries($user);
        } else {
            // Non-scoped role — clear any prior country bindings to avoid
            // confusion (those bindings have no effect for global roles).
            if (Schema::hasTable('user_countries')) {
                $user->countries()->sync([]);
            }
        }

        if (! $user->is_active) {
            if (confirm(label: 'الحساب غير مفعَّل — هل تريد تفعيله الآن؟', default: true)) {
                $user->forceFill(['is_active' => true])->save();
                $this->components->info('تم تفعيل الحساب.');
            }
        }
    }

    private function bindCountries(User $user): void
    {
        if (! Schema::hasTable('user_countries')) {
            $this->components->warn('جدول user_countries غير موجود — تخطّي ربط الدول.');

            return;
        }

        $countries = Country::query()
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en']);
        if ($countries->isEmpty()) {
            $this->components->warn('لا توجد دول مسجَّلة بعد. أضف الدول أولاً ثم أعد الإسناد.');

            return;
        }

        $current = $user->countries->pluck('id')->map(fn ($id) => (string) $id)->all();

        $selected = multiselect(
            label: 'اختر الدولة/الدول التي يعمل عليها هذا المستخدم',
            options: $countries->mapWithKeys(fn (Country $c) => [(string) $c->id => $this->countryLabel($c)])->all(),
            default: $current,
            required: true,
            scroll: 12,
        );

        $user->countries()->sync(array_map('intval', $selected));
        $this->components->info('تم تحديث ربط الدول: ' . implode(', ', $selected));
    }

    /** @return array<string, string> */
    private function roleOptions(): array
    {
        $labels = [
            'system_admin' => 'مدير النظام',
            'project_creator' => 'منشئ المشروع',
            'readiness_approver' => 'معتمد الجاهزية (حسب الدولة)',
            'project_executor' => 'منفذ المشروع (حسب الدولة)',
            'enhancer_entry_country' => 'مدخل معززات المشروع (حسب الدولة)',
            'enhancer_finance_central' => 'مدخل ومعتمد المعززات والحوالات (مركزي)',
            'final_report_preparer' => 'مُعدّ التقرير النهائي',
            'board_supervisor' => 'مشرف النظام / مجلس الإدارة',
        ];

        $options = [];
        foreach (Role::cases() as $role) {
            $slug = $role->value;
            $label = $labels[$slug] ?? $slug;
            $options[$slug] = "{$label}  ({$slug})";
        }

        return $options;
    }

    private function resolveUser(string $key): ?User
    {
        if (ctype_digit($key)) {
            return User::find((int) $key);
        }

        return User::where('email', $key)->first();
    }

    /**
     * Format a country for display, handling DBs that use either `name`
     * (singular) or the more common `name_ar` / `name_en` columns.
     */
    private function countryLabel(Country $country): string
    {
        $candidates = [
            $country->getAttribute('name_ar'),
            $country->getAttribute('name_en'),
            $country->getAttribute('name'),
            $country->getAttribute('iso2'),
        ];

        foreach ($candidates as $value) {
            if (! empty($value)) {
                return (string) $value;
            }
        }

        return '#' . $country->id;
    }

    private function legacyRoleFor(int $userId): ?string
    {
        if (! Schema::hasTable('users_legacy_role_backup')) {
            return null;
        }

        $row = DB::table('users_legacy_role_backup')->where('user_id', $userId)->first();

        return $row?->legacy_role;
    }
}
