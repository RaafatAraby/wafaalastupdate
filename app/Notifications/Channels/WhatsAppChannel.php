<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends a notification to the configured WhatsApp bridge over HTTP.
 *
 * The bridge is a small Node.js + Baileys service that exposes a
 * `POST /send` endpoint accepting `{ phone, message }` and authenticated
 * with an `X-API-Key` header. See `whatsapp-bridge/` in the deployment
 * package for the reference implementation.
 *
 * The notification class must implement `toWhatsapp($notifiable): array|string`
 * (or `toWhatsApp`). The return value can be:
 *   - a string (the message body)
 *   - an array with at least `body` and optional `to` (raw E.164 number)
 */
class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $base = (string) config('services.whatsapp.url', env('WHATSAPP_BRIDGE_URL', ''));
        $apiKey = (string) config('services.whatsapp.key', env('WHATSAPP_BRIDGE_KEY', ''));

        if ($base === '' || $apiKey === '') {
            return; // Bridge not configured — silently skip.
        }

        $payload = $this->resolvePayload($notifiable, $notification);
        if (! $payload) {
            return;
        }

        try {
            Http::withHeaders([
                'X-API-Key' => $apiKey,
                'Accept' => 'application/json',
            ])
                ->timeout(8)
                ->retry(2, 250)
                ->post(rtrim($base, '/').'/send', $payload);
        } catch (Throwable $e) {
            Log::warning('whatsapp.bridge.send_failed', [
                'error' => $e->getMessage(),
                'to' => $payload['phone'] ?? null,
            ]);
        }
    }

    /**
     * @return array{phone:string,message:string}|null
     */
    private function resolvePayload(object $notifiable, Notification $notification): ?array
    {
        $method = method_exists($notification, 'toWhatsapp')
            ? 'toWhatsapp'
            : (method_exists($notification, 'toWhatsApp') ? 'toWhatsApp' : null);

        if ($method === null) {
            return null;
        }

        /** @var array|string $result */
        $result = $notification->{$method}($notifiable);

        if (is_string($result)) {
            $body = $result;
            $phone = method_exists($notifiable, 'routeNotificationFor')
                ? $notifiable->routeNotificationFor('whatsapp')
                : ($notifiable->whatsapp_number ?? null);
        } else {
            $body = (string) ($result['body'] ?? '');
            $phone = $result['to'] ?? (
                method_exists($notifiable, 'routeNotificationFor')
                    ? $notifiable->routeNotificationFor('whatsapp')
                    : ($notifiable->whatsapp_number ?? null)
            );
        }

        $phone = $phone ? preg_replace('/\D+/', '', (string) $phone) : null;

        if (! $phone || $body === '') {
            return null;
        }

        return ['phone' => $phone, 'message' => $body];
    }
}
