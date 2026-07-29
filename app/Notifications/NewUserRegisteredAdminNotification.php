<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegisteredAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private User $newUser,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $role = $this->newUser->role === UserRole::INFLUENCER ? 'Créateur de contenu' : 'Vendeur';

        return (new MailMessage)
            ->subject('Nouvel inscrit sur MANTOTA')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line("Un nouvel utilisateur vient de s'inscrire sur la plateforme MANTOTA.")
            ->line('**Nom :** ' . $this->newUser->name)
            ->line('**Email :** ' . $this->newUser->email)
            ->line('**Role :** ' . $role)
            ->line('**Pays :** ' . ($this->newUser->country ?? 'Non renseigne'))
            ->line('**Date :** ' . $this->newUser->created_at->format('d/m/Y H:i'))
            ->action('Voir les utilisateurs', route('admin.users.index'))
            ->line('Restez informe des activites de la plateforme.');
    }

    public function toArray(object $notifiable): array
    {
        $role = $this->newUser->role === UserRole::INFLUENCER ? 'Créateur' : 'Vendeur';

        return [
            'type'    => 'new_user_registered',
            'title'   => 'Nouvel inscrit',
            'message' => $this->newUser->name . " ($role) vient de s'inscrire.",
            'user_id' => $this->newUser->id,
            'url'     => route('admin.users.show', $this->newUser->id),
            'color'   => 'blue',
        ];
    }
}
