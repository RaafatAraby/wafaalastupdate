<?php

namespace App\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InternalActionNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $context  structured project info, notes, actor name, …
     */
    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
        public ?string $event = null,
        public array $context = [],
    ) {}

    /**
     * Channels:
     *  - database: drives Filament's bell icon in the admin panel.
     *  - mail:     opt-in based on env/notifiable. Skipped automatically
     *              when the recipient has no email or SMTP isn't configured,
     *              so a missing mailer never breaks the underlying action.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->shouldMail($notifiable)) {
            $channels[] = 'mail';
        }

        if ($this->shouldWhatsApp($notifiable)) {
            $channels[] = \App\Notifications\Channels\WhatsAppChannel::class;
        }

        return $channels;
    }

    /**
     * Plain-text WhatsApp message used by WhatsAppChannel.
     */
    public function toWhatsapp(object $notifiable): string
    {
        $ctx = $this->context;
        $lines = [];

        $lines[] = '*' . $this->title . '*';
        $lines[] = '';
        $lines[] = $this->body;

        $details = [];
        if (! empty($ctx['project_title'])) {
            $details[] = 'اسم المشروع: ' . $ctx['project_title'];
        }
        if (! empty($ctx['project_number'])) {
            $details[] = 'رقم المشروع: ' . $ctx['project_number'];
        }
        if (! empty($ctx['country'])) {
            $details[] = 'الدولة: ' . $ctx['country'];
        }
        // Date: prefer the financial transaction date, then the attachment
        // upload date — both are populated by InternalNotifier from the
        // subject so callers don't need to pass them in.
        $eventDate = $ctx['transaction_date'] ?? ($ctx['attachment_date'] ?? null);
        if (! empty($eventDate)) {
            $details[] = 'التاريخ: ' . $eventDate;
        }
        if (! empty($ctx['transaction_type_label'])) {
            $details[] = 'نوع الحركة: ' . $ctx['transaction_type_label'];
        }
        if (! empty($ctx['attachment_category_label'])) {
            $details[] = 'نوع المرفق: ' . $ctx['attachment_category_label'];
        }
        if (! empty($ctx['amount'])) {
            $details[] = 'المبلغ: ' . $ctx['amount'];
        }
        if (! empty($ctx['state_label'])) {
            $details[] = 'الحالة: ' . $ctx['state_label'];
        }
        if (! empty($ctx['actor_name'])) {
            $details[] = 'بواسطة: ' . $ctx['actor_name'];
        }

        if (! empty($details)) {
            $lines[] = '';
            $lines[] = '— تفاصيل —';
            foreach ($details as $d) {
                $lines[] = '• ' . $d;
            }
        }

        if (! empty($ctx['notes'])) {
            $lines[] = '';
            $lines[] = '*ملاحظات:*';
            $lines[] = (string) $ctx['notes'];
        }

        if ($this->url) {
            $lines[] = '';
            $lines[] = 'عرض في النظام: ' . $this->url;
        }

        $lines[] = '';
        $lines[] = '— نظام وفاء المحسنين';

        return implode("\n", $lines);
    }

    /**
     * Payload consumed by Filament's database notifications panel. Using
     * Filament's helper ensures the notification renders as a card with a
     * clickable title rather than raw JSON.
     */
    public function toDatabase(object $notifiable): array
    {
        // Build a Filament-compatible database notification payload. The
        // `Action` namespace differs between Filament major versions
        // (`Filament\Actions\Action` in v5, `Filament\Notifications\Actions\Action`
        // in v3), so we resolve it dynamically and silently fall back to
        // returning the raw `url` in the data payload (the bell icon will
        // still render the title/body, and our custom view code can use
        // the `url` field to build the link).
        $payload = FilamentNotification::make()
            ->title($this->title)
            ->body($this->body)
            ->icon($this->iconForEvent())
            ->iconColor($this->colorForEvent());

        if ($this->url !== null && $this->url !== '') {
            $actionClass = null;
            foreach ([
                '\\Filament\\Actions\\Action',
                '\\Filament\\Notifications\\Actions\\Action',
            ] as $candidate) {
                if (class_exists($candidate)) {
                    $actionClass = $candidate;
                    break;
                }
            }

            if ($actionClass !== null) {
                try {
                    $payload->actions([
                        $actionClass::make('open')
                            ->label('عرض في النظام')
                            ->url($this->url, shouldOpenInNewTab: false),
                    ]);
                } catch (\Throwable) {
                    // ignore — the URL is still available in the data payload below.
                }
            }
        }

        return array_merge($payload->getDatabaseMessage(), [
            // Plain copies for any non-Filament consumer.
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'event' => $this->event,
            'context' => $this->context,
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = new MailMessage();
        $message->subject('[نظام وفاء المحسنين] ' . $this->title)
            ->view('emails.internal-action', [
                'title' => $this->title,
                'body' => $this->body,
                'url' => $this->url,
                'event' => $this->event,
                'context' => $this->context,
                'recipient' => $notifiable,
            ]);

        return $message;
    }

    private function shouldWhatsApp(object $notifiable): bool
    {
        $number = $notifiable->whatsapp_number ?? null;
        if (empty($number)) {
            return false;
        }

        $base = (string) (config('services.whatsapp.url') ?? env('WHATSAPP_BRIDGE_URL', ''));
        $key = (string) (config('services.whatsapp.key') ?? env('WHATSAPP_BRIDGE_KEY', ''));

        return $base !== '' && $key !== '';
    }

    private function shouldMail(object $notifiable): bool
    {
        $email = $notifiable->email ?? null;
        if (empty($email)) {
            return false;
        }

        $mailer = (string) config('mail.default', '');
        $fromAddress = config('mail.from.address');

        if ($mailer === '' || $mailer === 'array' || $mailer === 'log') {
            return false;
        }

        return ! empty($fromAddress);
    }

    private function iconForEvent(): string
    {
        return match (true) {
            $this->event === null => 'heroicon-o-bell-alert',
            str_contains($this->event, 'approved') => 'heroicon-o-check-circle',
            str_contains($this->event, 'rejected') => 'heroicon-o-x-circle',
            str_contains($this->event, 'rolled_back') => 'heroicon-o-arrow-uturn-left',
            str_contains($this->event, 'closed') => 'heroicon-o-lock-closed',
            str_contains($this->event, 'created') => 'heroicon-o-plus-circle',
            str_contains($this->event, 'final_report') => 'heroicon-o-document-text',
            str_contains($this->event, 'execution') => 'heroicon-o-play-circle',
            str_contains($this->event, 'readiness') => 'heroicon-o-clipboard-document-check',
            str_contains($this->event, 'attachment') => 'heroicon-o-paper-clip',
            str_contains($this->event, 'financial') => 'heroicon-o-banknotes',
            default => 'heroicon-o-bell-alert',
        };
    }

    private function colorForEvent(): string
    {
        return match (true) {
            $this->event === null => 'primary',
            str_contains($this->event, 'approved'),
            str_contains($this->event, 'closed') => 'success',
            str_contains($this->event, 'rejected'),
            str_contains($this->event, 'rolled_back') => 'danger',
            str_contains($this->event, 'created') => 'primary',
            default => 'primary',
        };
    }
}
