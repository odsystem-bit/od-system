<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\WhatsappChannel;
use App\Channels\WhatsappMessage;
use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Campaign $campaign,
        private string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', WhatsappChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Campagne rejetee — ' . $this->campaign->title)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Votre campagne **"' . $this->campaign->title . '"** a ete rejetee par notre systeme de moderation.')
            ->line('**Raison du rejet :** ' . $this->reason)
            ->line('Veuillez modifier le contenu de votre campagne pour respecter les conditions d\'utilisation de la plateforme.')
            ->action('Voir mes campagnes', route('vendor.campaigns.index'))
            ->line('Merci de faire confiance a MANTOTA.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'campaign_rejected',
            'title'       => 'Campagne rejetee',
            'message'     => 'Votre campagne "' . $this->campaign->title . '" a ete rejetee. Raison : ' . $this->reason,
            'reason'      => $this->reason,
            'campaign_id' => $this->campaign->id,
            'url'         => route('vendor.campaigns.index'),
            'color'       => 'red',
        ];
    }

    public function toWhatsapp(object $notifiable): WhatsappMessage
    {
        return new WhatsappMessage(
            "MANTOTA — Votre campagne \"{$this->campaign->title}\" a ete rejetee. Raison : {$this->reason}. Veuillez modifier votre campagne sur la plateforme."
        );
    }
}
