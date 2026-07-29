<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycSubmittedAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private User $submittedUser,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $role = $this->submittedUser->role === 'influencer' ? 'Createur de contenu' : 'Vendeur';

        return (new MailMessage)
            ->subject('Nouvelle soumission KYC a verifier')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Un utilisateur vient de soumettre ses documents KYC pour verification.')
            ->line('**Nom :** ' . $this->submittedUser->name)
            ->line('**Email :** ' . $this->submittedUser->email)
            ->line('**Role :** ' . $role)
            ->line('Veuillez examiner ce dossier dans les plus brefs delais.')
            ->action('Verifier le dossier KYC', route('admin.kyc.index'))
            ->line('Merci de contribuer a la securite de la plateforme MANTOTA.');
    }

    public function toArray(object $notifiable): array
    {
        $role = $this->submittedUser->role === 'influencer' ? 'Createur' : 'Vendeur';

        return [
            'type'    => 'kyc_submitted',
            'title'   => 'Nouvelle demande KYC',
            'message' => $this->submittedUser->name . " ($role) a soumis ses documents KYC.",
            'user_id' => $this->submittedUser->id,
            'url'     => route('admin.kyc.index'),
            'color'   => 'orange',
        ];
    }
}
