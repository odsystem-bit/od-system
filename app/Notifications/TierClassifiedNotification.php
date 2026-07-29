<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\WhatsappChannel;
use App\Channels\WhatsappMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TierClassifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $tier, // 'bronze', 'argent', 'or'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', WhatsappChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tierLabel = $this->tierLabel();
        $tierEmoji = $this->tierEmoji();

        return (new MailMessage)
            ->subject("Felicitations ! Vous etes classe $tierLabel $tierEmoji")
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Vos profils ont bien ete recus et analyses par notre equipe.')
            ->line("Nous sommes heureux de vous annoncer que vous etes classe dans la categorie **$tierLabel** $tierEmoji.")
            ->line('Vous pouvez desormais participer aux campagnes publicitaires de cette categorie.')
            ->line('Continuez a progresser afin de debloquer l\'option VIP, qui vous permettra de proposer vos services de creation de videos UGC, avec IA ou en production humaine.')
            ->action('Voir mon espace', route('home'))
            ->line('Felicitations et bon travail !')
            ->salutation('L\'equipe MANTOTA');
    }

    public function toArray(object $notifiable): array
    {
        $tierLabel = $this->tierLabel();

        return [
            'type'    => 'tier_classified',
            'title'   => "Classe $tierLabel !",
            'message' => "Felicitations ! Vous etes desormais dans la categorie $tierLabel. Participez aux campagnes de votre categorie.",
            'tier'    => $this->tier,
            'url'     => null,
            'color'   => $this->tierColor(),
        ];
    }

    public function toWhatsapp(object $notifiable): WhatsappMessage
    {
        $tierLabel = $this->tierLabel();
        $tierEmoji = $this->tierEmoji();

        return new WhatsappMessage(
            "MANTOTA $tierEmoji Felicitations {$notifiable->name} ! Vous etes classe dans la categorie $tierLabel. "
            . "Participez aux campagnes de votre niveau sur mantota.com"
        );
    }

    private function tierLabel(): string
    {
        return match ($this->tier) {
            'bronze' => 'Bronze',
            'argent' => 'Argent',
            'or'     => 'Or',
            default  => ucfirst($this->tier),
        };
    }

    private function tierEmoji(): string
    {
        return match ($this->tier) {
            'bronze' => '🥉',
            'argent' => '🥈',
            'or'     => '🥇',
            default  => '⭐',
        };
    }

    private function tierColor(): string
    {
        return match ($this->tier) {
            'bronze' => '#cd7f32',
            'argent' => '#c0c0c0',
            'or'     => '#ffd700',
            default  => 'blue',
        };
    }
}
