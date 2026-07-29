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

class NewSaleNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Order $order,
        private string $recipientRole, // 'vendor' or 'influencer'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', WhatsappChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = $this->recipientRole === 'vendor'
            ? number_format((float) $this->order->vendor_earnings, 0, ',', ' ')
            : number_format((float) $this->order->commission_amount, 0, ',', ' ');

        $label = $this->recipientRole === 'vendor'
            ? 'Vous avez recu une nouvelle vente'
            : 'Vous avez gagne une commission';

        return (new MailMessage)
            ->subject('Nouvelle vente -- ' . $this->order->reference)
            ->greeting('Felicitations !')
            ->line($label . ' via MANTOTA.')
            ->line('Reference : ' . $this->order->reference)
            ->line('Montant : ' . $amount . ' FCFA')
            ->action('Voir mes commandes', $this->recipientRole === 'vendor'
                ? route('vendor.orders.index')
                : route('influencer.dashboard'))
            ->line('Merci de faire confiance a MANTOTA.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'new_sale',
            'title'   => 'Nouvelle vente -- ' . $this->order->reference,
            'message' => $this->recipientRole === 'vendor'
                ? 'Vente de ' . number_format((float) $this->order->vendor_earnings, 0, ',', ' ') . ' FCFA'
                : 'Commission de ' . number_format((float) $this->order->commission_amount, 0, ',', ' ') . ' FCFA',
            'url'     => $this->recipientRole === 'vendor'
                ? route('vendor.orders.index')
                : route('influencer.dashboard'),
            'color'   => 'teal',
        ];
    }

    public function toWhatsapp(object $notifiable): WhatsappMessage
    {
        $amount = $this->recipientRole === 'vendor'
            ? number_format((float) $this->order->vendor_earnings, 0, ',', ' ')
            : number_format((float) $this->order->commission_amount, 0, ',', ' ');

        return new WhatsappMessage("MANTOTA -- Nouvelle vente !\nReference : {$this->order->reference}\nMontant : {$amount} FCFA");
    }
}
