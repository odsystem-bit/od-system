<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\WhatsappChannel;
use App\Channels\WhatsappMessage;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawalProcessedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Transaction $transaction,
        private string $status, // 'completed' or 'failed'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', WhatsappChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format((float) $this->transaction->amount_target, 0, ',', ' ');

        $mail = (new MailMessage)
            ->subject('Retrait ' . ($this->status === 'completed' ? 'Approuve' : 'Rejete'))
            ->greeting('Bonjour ' . $notifiable->name . ',');

        if ($this->status === 'completed') {
            $mail->line('Votre demande de retrait de ' . $amount . ' FCFA a ete approuvee.')
                 ->line('Le transfert a ete effectue vers votre compte mobile money.');
        } else {
            $mail->line('Votre demande de retrait de ' . $amount . ' FCFA a ete rejetee.')
                 ->line('Le montant a ete recredite dans votre portefeuille MANTOTA.');
        }

        return $mail
            ->action('Voir mon portefeuille', $notifiable->role?->value === 'vendor'
                ? route('vendor.wallet.index')
                : route('influencer.wallet.index'))
            ->line('Merci de faire confiance a MANTOTA.');
    }

    public function toArray(object $notifiable): array
    {
        $amount = number_format((float) $this->transaction->amount_target, 0, ',', ' ');

        return [
            'type'    => 'withdrawal',
            'title'   => 'Retrait ' . ($this->status === 'completed' ? 'approuve' : 'rejete'),
            'message' => $this->status === 'completed'
                ? 'Retrait de ' . $amount . ' FCFA envoye.'
                : 'Retrait de ' . $amount . ' FCFA rejete. Solde recredite.',
            'url'     => $notifiable->role?->value === 'vendor'
                ? route('vendor.wallet.index')
                : route('influencer.wallet.index'),
            'color'   => $this->status === 'completed' ? 'teal' : 'red',
        ];
    }

    public function toWhatsapp(object $notifiable): WhatsappMessage
    {
        $amount = number_format((float) $this->transaction->amount_target, 0, ',', ' ');

        $text = $this->status === 'completed'
            ? "MANTOTA -- Retrait approuve ! {$amount} FCFA envoye sur votre mobile money."
            : "MANTOTA -- Retrait rejete. {$amount} FCFA recredite dans votre portefeuille.";

        return new WhatsappMessage($text);
    }
}
