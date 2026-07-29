<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemErrorNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $errorLevel,
        private string $errorMessage,
        private string $errorFile,
        private string $errorLine,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('MANTOTA -- Erreur systeme : ' . $this->errorLevel)
            ->greeting('Alerte systeme')
            ->line('**Niveau** : ' . $this->errorLevel)
            ->line('**Message** : ' . mb_substr($this->errorMessage, 0, 300))
            ->line('**Fichier** : ' . $this->errorFile . ' (ligne ' . $this->errorLine . ')')
            ->action('Voir le tableau de sante', route('admin.health.index'))
            ->line('Veuillez intervenir rapidement.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'system_error',
            'title'   => "Erreur systeme : {$this->errorLevel}",
            'message' => mb_substr($this->errorMessage, 0, 200),
            'file'    => $this->errorFile,
            'line'    => $this->errorLine,
            'url'     => route('admin.health.index'),
            'color'   => $this->errorLevel === 'CRITICAL' ? 'red' : 'orange',
        ];
    }
}
