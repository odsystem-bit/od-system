<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\WhatsappChannel;
use App\Channels\WhatsappMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification envoyee aux administrateurs lorsque le robot KYC
 * rejette automatiquement un dossier (mineur, carte expiree, etc.).
 */
class KycAutoRejectedAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private User $rejectedUser,
        private string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Robot KYC -- Rejet automatique')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Le robot KYC a automatiquement rejete le dossier de **' . $this->rejectedUser->name . '** (' . $this->rejectedUser->email . ').')
            ->line('**Raison :** ' . $this->reason)
            ->line('L\'utilisateur a ete notifie et pourra re-soumettre son dossier apres correction.')
            ->action('Voir les dossiers KYC', route('admin.kyc.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'kyc_auto_rejected',
            'title'   => 'Robot KYC -- Rejet auto',
            'message' => 'Dossier de ' . $this->rejectedUser->name . ' rejete automatiquement. Raison : ' . $this->reason,
            'user_id' => $this->rejectedUser->id,
            'url'     => null,
            'color'   => 'amber',
        ];
    }
}
