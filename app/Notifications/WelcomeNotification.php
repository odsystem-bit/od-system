<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\UserRole;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($notifiable->role === UserRole::INFLUENCER) {
            return $this->influencerMail($notifiable);
        }

        return $this->vendorMail($notifiable);
    }

    private function influencerMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenue sur MANTOTA, ' . $notifiable->name . ' !')
            ->greeting('Bonjour ' . $notifiable->name . ' !')
            ->line('Bienvenue sur **MANTOTA**, la plateforme qui connecte les créateurs de contenu aux marques.')
            ->line('Pour commencer à recevoir des campagnes et gagner de l\'argent, voici les étapes importantes :')
            ->line('**1. Vérifiez votre email** — Entrez le code de vérification que nous venons de vous envoyer.')
            ->line('**2. Complétez votre profil** — Ajoutez vos réseaux sociaux, votre photo et votre bio.')
            ->line('**3. Faites votre KYC** — Vérifiez votre identité pour pouvoir effectuer des retraits.')
            ->line('Une fois ces étapes terminées, vous apparaîtrez dans le catalogue des créateurs et pourrez recevoir des commandes de campagnes.')
            ->action('Compléter mon profil', route('influencer.dashboard'))
            ->line('À très bientôt sur MANTOTA !')
            ->salutation('L\'équipe MANTOTA');
    }

    private function vendorMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenue sur MANTOTA, ' . $notifiable->name . ' !')
            ->greeting('Bonjour ' . $notifiable->name . ' !')
            ->line('Bienvenue sur **MANTOTA**, la plateforme qui connecte les vendeurs aux créateurs de contenu pour booster vos ventes.')
            ->line('Voici comment démarrer :')
            ->line('**1. Vérifiez votre email** — Entrez le code de vérification que nous venons de vous envoyer.')
            ->line('**2. Complétez votre profil** — Ajoutez les informations de votre entreprise.')
            ->line('**3. Créez votre première campagne** — Lancez une campagne CPC pour que les créateurs de contenu promeuvent vos produits.')
            ->line('**4. Déposez du solde** — Rechargez votre portefeuille pour financer vos campagnes publicitaires.')
            ->line('Nos créateurs de contenu sont prêts à promouvoir vos produits auprès de leur audience.')
            ->action('Accéder à mon espace', route('vendor.dashboard'))
            ->line('À très bientôt sur MANTOTA !')
            ->salutation('L\'équipe MANTOTA');
    }
}
