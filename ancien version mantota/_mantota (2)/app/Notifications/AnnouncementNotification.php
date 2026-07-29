<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnouncementNotification extends Notification
{
    use Queueable;

    private string $message;
    private string $targetRole;

    public function __construct(
        Announcement $announcement,
        private bool $sendEmail = false,
    ) {
        // Stocker les valeurs scalaires pour eviter la serialisation du modele
        $this->message    = $announcement->message;
        $this->targetRole = $announcement->target_role ?? 'all';
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->sendEmail) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('MANTOTA -- Nouvelle annonce')
            ->greeting('Bonjour ' . ($notifiable->name ?? '') . ',')
            ->line($this->message)
            ->action('Acceder a la plateforme', url('/'))
            ->line('Merci de faire confiance a MANTOTA.');
    }

    public function toArray(object $notifiable): array
    {
        $role = $notifiable->role?->value ?? '';

        return [
            'type'    => 'announcement',
            'title'   => 'Nouvelle annonce MANTOTA',
            'message' => mb_substr($this->message, 0, 200),
            'url'     => $role === 'vendor' ? route('vendor.dashboard') : ($role === 'influencer' ? route('influencer.dashboard') : '/'),
            'color'   => 'purple',
        ];
    }
}
