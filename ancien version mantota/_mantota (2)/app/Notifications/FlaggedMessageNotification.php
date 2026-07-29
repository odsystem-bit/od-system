<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FlaggedMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $originalMessage,
        private string $source,
        private string $senderName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('MANTOTA -- Message suspect detecte')
            ->greeting('Bonjour ' . ($notifiable->name ?? '') . ',')
            ->line('Un message suspect a ete detecte de la part de **' . $this->senderName . '** (' . $this->source . ').')
            ->line('Extrait : ' . mb_substr($this->originalMessage, 0, 200))
            ->action('Voir les messages signales', route('admin.flagged-messages.index'))
            ->line('Veuillez verifier ce message.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'flagged_message',
            'title'       => 'Message suspect detecte',
            'message'     => $this->senderName . ' a envoye un message suspect (' . $this->source . ').',
            'original'    => mb_substr($this->originalMessage, 0, 200),
            'source'      => $this->source,
            'sender_name' => $this->senderName,
            'url'         => route('admin.flagged-messages.index'),
            'color'       => 'orange',
        ];
    }
}
