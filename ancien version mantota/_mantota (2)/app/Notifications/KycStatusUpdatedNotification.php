<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\WhatsappChannel;
use App\Channels\WhatsappMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $status, // 'approved' or 'rejected'
        private ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', WhatsappChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Verification KYC | ' . ($this->status === 'approved' ? 'Approuve' : 'Rejete'))
            ->greeting('Bonjour ' . $notifiable->name . ',');

        if ($this->status === 'approved') {
            $mail->line('Votre dossier KYC a ete approuve avec succes.')
                 ->line('Vous pouvez desormais effectuer des retraits et acceder a toutes les fonctionnalites de la plateforme.')
                 ->action('Acceder a mon espace', route('home'));
        } else {
            $mail->line('Votre dossier KYC a ete rejete.');
            if ($this->reason) {
                $mail->line('**Raison du rejet :** ' . $this->reason);
            }
            $mail->line('Veuillez corriger les points mentionnes ci-dessus et soumettre a nouveau votre dossier.')
                 ->action('Soumettre a nouveau', route('home'));
        }

        return $mail->line('Merci de faire confiance a MANTOTA.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'kyc_status',
            'title'   => 'KYC ' . ($this->status === 'approved' ? 'Approuve' : 'Rejete'),
            'message' => $this->status === 'approved'
                ? 'Votre dossier KYC a ete valide. Acces complet active.'
                : 'Votre dossier KYC a ete rejete.' . ($this->reason ? ' Raison : ' . $this->reason : ' Veuillez re-soumettre.'),
            'reason'  => $this->reason,
            'url'     => null,
            'color'   => $this->status === 'approved' ? 'teal' : 'red',
        ];
    }

    public function toWhatsapp(object $notifiable): WhatsappMessage
    {
        $text = $this->status === 'approved'
            ? "MANTOTA -- Votre KYC est approuve ! Vous avez desormais acces a toutes les fonctionnalites."
            : "MANTOTA -- Votre KYC a ete rejete." . ($this->reason ? " Raison : {$this->reason}" : " Veuillez re-soumettre vos documents sur la plateforme.");

        return new WhatsappMessage($text);
    }
}
