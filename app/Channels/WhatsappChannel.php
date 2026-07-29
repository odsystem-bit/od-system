<?php

declare(strict_types=1);

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappChannel
{
    /**
     * Send a WhatsApp notification via Evolution API (open-source).
     * Simple POST HTTP — no token required.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $to = $notifiable->routeNotificationForWhatsapp();

        if (! $to) {
            return;
        }

        $message = method_exists($notification, 'toWhatsapp')
            ? $notification->toWhatsapp($notifiable)
            : null;

        if (! $message || empty($message->content)) {
            return;
        }

        $apiUrl = config('services.whatsapp.api_url');

        if (! $apiUrl) {
            Log::debug('WhatsApp channel: WHATSAPP_API_URL not configured.');
            return;
        }

        try {
            Http::timeout(10)->post($apiUrl, [
                'number' => $to,
                'text'   => $message->content,
            ]);
        } catch (\Throwable $e) {
            Log::error('WhatsApp notification failed', [
                'number' => $to,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
