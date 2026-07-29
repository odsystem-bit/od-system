<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DigitalProductDeliveredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Order $order,
        private string $accessUrl,
        private bool $isFileDownload = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $productName = $this->order->product?->name ?? 'Produit digital';

        $mail = (new MailMessage)
            ->subject('Votre produit digital est pret — ' . $this->order->reference)
            ->greeting('Merci pour votre achat !')
            ->line("Votre commande **{$this->order->reference}** pour **{$productName}** a ete confirmee.");

        if ($this->isFileDownload) {
            $mail->line('Cliquez sur le bouton ci-dessous pour telecharger vos fichiers (format ZIP) :')
                 ->action('Telecharger mes fichiers', $this->accessUrl)
                 ->line('Ce lien est securise et lie a votre commande.');
        } else {
            $mail->line('Cliquez sur le bouton ci-dessous pour acceder a votre produit :')
                 ->action('Acceder a mon produit', $this->accessUrl);
        }

        return $mail
            ->line('Vous pouvez egalement suivre votre commande via le lien de suivi.')
            ->line('Si vous rencontrez un probleme, n\'hesitez pas a contacter le support MANTOTA.')
            ->salutation('L\'equipe MANTOTA');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'      => 'digital_product_delivered',
            'order_ref' => $this->order->reference,
        ];
    }
}
