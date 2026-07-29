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

class NewChatMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private ServiceOrder $serviceOrder,
        private string $senderName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', WhatsappChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouveau message -- MANTOTA Studios')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line($this->senderName . ' vous a envoye un message concernant votre commande Studios #' . $this->serviceOrder->id . '.')
            ->action('Voir la conversation', $this->getUrl($notifiable))
            ->line('Merci de faire confiance a MANTOTA.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'chat_message',
            'title'   => 'Nouveau message',
            'message' => $this->senderName . ' -- Commande Studios #' . $this->serviceOrder->id,
            'url'     => $this->getUrl($notifiable),
            'color'   => 'blue',
        ];
    }

    public function toWhatsapp(object $notifiable): WhatsappMessage
    {
        return new WhatsappMessage("MANTOTA -- Nouveau message de {$this->senderName} pour la commande Studios #{$this->serviceOrder->id}. Consultez votre espace pour repondre.");
    }

    private function getUrl(object $notifiable): string
    {
        return $notifiable->role?->value === 'vendor'
            ? route('vendor.service-orders.show', $this->serviceOrder->id)
            : route('influencer.service-orders.show', $this->serviceOrder->id);
    }
}
