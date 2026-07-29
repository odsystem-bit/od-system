<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\WhatsappChannel;
use App\Channels\WhatsappMessage;
use App\Models\ServiceOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UgcOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private ServiceOrder $serviceOrder,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', WhatsappChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle commande MANTOTA Studios')
            ->greeting('Bonjour ' . $notifiable->name . ' !')
            ->line('Vous avez recu une nouvelle commande MANTOTA Studios.')
            ->line('Montant : ' . number_format((float) $this->serviceOrder->amount, 0, ',', ' ') . ' FCFA')
            ->line('Brief : ' . \Illuminate\Support\Str::limit($this->serviceOrder->brief ?? '', 100))
            ->action('Voir la commande', route('influencer.service-orders.show', $this->serviceOrder->id))
            ->line('Merci de faire confiance a MANTOTA.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'ugc_order',
            'title'   => 'Nouvelle commande Studios',
            'message' => 'Commande UGC de ' . number_format((float) $this->serviceOrder->amount, 0, ',', ' ') . ' FCFA',
            'url'     => route('influencer.service-orders.show', $this->serviceOrder->id),
            'color'   => 'purple',
        ];
    }

    public function toWhatsapp(object $notifiable): WhatsappMessage
    {
        return new WhatsappMessage(
            "MANTOTA Studios -- Nouvelle commande !\nMontant : " . number_format((float) $this->serviceOrder->amount, 0, ',', ' ') . " FCFA\nConsultez votre espace créateur de contenu pour les details."
        );
    }
}
