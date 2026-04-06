<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AlertNotifier
{
    public static function storeAndSend(
        ?int $projectId,
        string $type,
        string $title,
        string $body,
        string $severity = 'info'
    ): void {
        DB::table('alerts')->insert([
            'project_id' => $projectId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'severity' => $severity,
            'is_read' => false,
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        self::sendEmail($title, $body, $severity);
        self::sendWhatsApp($projectId, $type, $title, $body, $severity);
    }

    protected static function sendEmail(string $title, string $body, string $severity): void
    {
        $emails = collect(explode(',', (string) env('ALERT_EMAIL_TO', '')))
            ->map(fn ($email) => trim($email))
            ->filter()
            ->values();

        if ($emails->isEmpty()) {
            return;
        }

        foreach ($emails as $email) {
            try {
                Mail::raw($body, function ($message) use ($email, $title, $severity) {
                    $message->to($email)->subject("[$severity] $title");
                });
            } catch (Throwable $e) {
                Log::warning('Alert email failed', ['email' => $email, 'error' => $e->getMessage()]);
            }
        }
    }

    protected static function sendWhatsApp(?int $projectId, string $type, string $title, string $body, string $severity): void
    {
        $webhookUrl = trim((string) env('WHATSAPP_WEBHOOK_URL', ''));

        if ($webhookUrl === '') {
            return;
        }

        $phones = collect(explode(',', (string) env('WHATSAPP_TO', '')))
            ->map(fn ($phone) => trim($phone))
            ->filter()
            ->values()
            ->all();

        try {
            $request = Http::timeout(20);
            $token = trim((string) env('WHATSAPP_WEBHOOK_TOKEN', ''));

            if ($token !== '') {
                $request = $request->withToken($token);
            }

            $request->post($webhookUrl, [
                'project_id' => $projectId,
                'type' => $type,
                'title' => $title,
                'message' => $body,
                'severity' => $severity,
                'phones' => $phones,
            ]);
        } catch (Throwable $e) {
            Log::warning('Alert whatsapp failed', ['error' => $e->getMessage()]);
        }
    }
}
