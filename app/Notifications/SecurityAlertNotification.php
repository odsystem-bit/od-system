<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $message
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[MANTOTA SECURITE] ' . $this->title)
            ->greeting('Alerte de securite')
            ->line($this->message)
            ->line('Date : ' . now()->format('d/m/Y H:i:s'))
            ->action('Voir le panneau de securite', url('/admin/security'))
            ->salutation('Systeme de detection MANTOTA');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'security_alert',
            'title'   => $this->title,
            'message' => $this->message,
        ];
    }
}
