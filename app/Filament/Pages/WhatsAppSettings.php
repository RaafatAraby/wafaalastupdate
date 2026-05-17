<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use UnitEnum;

class WhatsAppSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string|UnitEnum|null $navigationGroup = 'النظام';
    protected static ?string $navigationLabel = 'إعدادات واتساب';
    protected static ?string $title = 'إعدادات واتساب — ربط الجهاز';
    protected static ?int $navigationSort = 95;

    protected string $view = 'filament.pages.whatsapp-settings';

    public array $status = [
        'connected' => false,
        'state' => 'unknown',
        'phone' => null,
        'lastConnectedAt' => null,
        'qr' => null,
        'qrAge' => null,
        'configured' => false,
        'error' => null,
    ];

    public ?string $testNumber = null;
    public ?string $testMessage = 'رسالة اختبار من نظام وفاء المحسنين.';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return method_exists($user, 'hasRole')
            ? $user->hasRole('system_admin')
            : false;
    }

    public function mount(): void
    {
        $this->refreshStatus();
    }

    /**
     * Default "disconnected" status used as a baseline for every error path.
     * This prevents stale 'connected'/'phone'/'qr' values from a previous
     * successful refresh from leaking into the UI alongside a fresh error.
     */
    protected function disconnectedStatus(): array
    {
        return [
            'connected'       => false,
            'state'           => 'unknown',
            'phone'           => null,
            'lastConnectedAt' => null,
            'qr'              => null,
            'qrAge'           => null,
            'configured'      => false,
            'error'           => null,
        ];
    }

    public function refreshStatus(): void
    {
        [$base, $key] = $this->bridge();
        if (! $base) {
            $this->status = array_merge($this->disconnectedStatus(), [
                'configured' => false,
                'error' => 'الـ Bridge غير مهيأ بعد. أضف WHATSAPP_BRIDGE_URL و WHATSAPP_BRIDGE_KEY في ملف .env.',
            ]);
            return;
        }

        try {
            $response = Http::withHeaders(['X-API-Key' => $key])
                ->timeout(6)
                ->get($base.'/status');

            if (! $response->ok()) {
                $this->status = array_merge($this->disconnectedStatus(), [
                    'configured' => true,
                    'error' => 'فشل الاتصال بالـ Bridge. الكود: '.$response->status(),
                ]);
                return;
            }

            $payload = $response->json();
            $this->status = [
                'connected' => (bool) ($payload['connected'] ?? false),
                'state' => (string) ($payload['state'] ?? 'unknown'),
                'phone' => $payload['phone'] ?? null,
                'lastConnectedAt' => $payload['lastConnectedAt'] ?? null,
                'qr' => $payload['qr'] ?? null,
                'qrAge' => $payload['qrAge'] ?? null,
                'configured' => true,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            $this->status = array_merge($this->disconnectedStatus(), [
                'configured' => true,
                'error' => 'تعذّر الوصول للـ Bridge: '.$e->getMessage(),
            ]);
        }
    }

    public function disconnect(): void
    {
        [$base, $key] = $this->bridge();
        if (! $base) {
            return;
        }

        try {
            Http::withHeaders(['X-API-Key' => $key])
                ->timeout(6)
                ->post($base.'/disconnect');

            FilamentNotification::make()
                ->title('تم فصل الاتصال')
                ->body('سيتم توليد رمز QR جديد خلال ثوانٍ.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            FilamentNotification::make()
                ->title('فشل فصل الاتصال')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->refreshStatus();
    }

    public function sendTest(): void
    {
        [$base, $key] = $this->bridge();
        if (! $base) {
            FilamentNotification::make()->title('Bridge غير مهيأ')->danger()->send();
            return;
        }

        $phone = preg_replace('/\D+/', '', (string) $this->testNumber);
        if (! $phone || strlen($phone) < 8) {
            FilamentNotification::make()
                ->title('رقم غير صالح')
                ->body('أدخل الرقم بالصيغة الدولية بدون + ولا 00.')
                ->warning()
                ->send();
            return;
        }

        try {
            $response = Http::withHeaders(['X-API-Key' => $key])
                ->timeout(8)
                ->post($base.'/send', [
                    'phone' => $phone,
                    'message' => $this->testMessage ?: 'اختبار',
                ]);

            if ($response->ok()) {
                FilamentNotification::make()
                    ->title('تم الإرسال')
                    ->body('تحقق من واتساب الرقم: '.$phone)
                    ->success()
                    ->send();
            } else {
                FilamentNotification::make()
                    ->title('فشل الإرسال')
                    ->body('Bridge أعاد كود '.$response->status().': '.$response->body())
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            FilamentNotification::make()
                ->title('استثناء أثناء الإرسال')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function bridge(): array
    {
        $base = trim((string) (config('services.whatsapp.url') ?? env('WHATSAPP_BRIDGE_URL', '')));
        $key = (string) (config('services.whatsapp.key') ?? env('WHATSAPP_BRIDGE_KEY', ''));

        if ($base === '') {
            return [null, null];
        }

        return [rtrim($base, '/'), $key];
    }
}
