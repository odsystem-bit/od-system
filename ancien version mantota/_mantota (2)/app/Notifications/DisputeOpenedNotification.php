<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\WhatsappChannel;
use App\Channels\WhatsappMessage;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * DisputeOpenedNotification -- Envoyee au vendeur lorsqu'un client
 * ouvre un litige sur une commande.
 *
 * Les fonds restent bloques en escrow jusqu'a la resolution.
 */
class DisputeOpenedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Order $order,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [
            'database',
            'mail',
            WhatsappChannel::class,
            // Futur : canal SMS local
            // SmsChannel::class,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Litige ouvert -- ' . $this->order->reference)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Un litige a ete ouvert sur la commande ' . $this->order->reference . '.')
            ->line('Motif : ' . ($this->order->dispute_reason ?? 'Non precise'))
            ->line('Les fonds restent bloques en sequestre jusqu\'a la resolution du litige.')
            ->line('Vous pouvez repondre et soumettre votre defense dans le chat de litige.')
            ->action('Voir le litige', route('vendor.orders.index'))
            ->line('Merci de faire confiance a MANTOTA.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'dispute_opened',
            'title'   => 'Litige ouvert -- ' . $this->order->reference,
            'message' => 'Le client a signale un probleme. Fonds en sequestre.',
            'url'     => route('vendor.orders.index'),
            'color'   => 'red',
        ];
    }

    public function toWhatsapp(object $notifiable): WhatsappMessage
    {
        return new WhatsappMessage(
            "MANTOTA -- Litige ouvert sur la commande {$this->order->reference}.\nMotif : " . ($this->order->dispute_reason ?? 'Non precise') . "\nConsultez votre espace vendeur pour repondre."
        );
    }
}
