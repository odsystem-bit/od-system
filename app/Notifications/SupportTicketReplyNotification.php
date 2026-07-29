<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private SupportTicket $ticket,
        private string $senderName,
        private bool $isAdminReply,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->isAdminReply
            ? 'Reponse du support -- Ticket #' . $this->ticket->reference_code
            : 'Nouveau message support -- Ticket #' . $this->ticket->reference_code;

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line($this->senderName . ' a repondu sur le ticket "' . $this->ticket->subject . '" (#' . $this->ticket->reference_code . ').')
            ->action('Voir le ticket', $this->getUrl($notifiable))
            ->line('Merci de faire confiance a MANTOTA.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'support_reply',
            'title'   => $this->isAdminReply ? 'Reponse du support' : 'Nouveau message support',
            'message' => $this->senderName . ' -- Ticket #' . $this->ticket->reference_code . ' : ' . $this->ticket->subject,
            'url'     => $this->getUrl($notifiable),
            'color'   => 'blue',
        ];
    }

    private function getUrl(object $notifiable): string
    {
        $role = $notifiable->role?->value ?? '';

        if ($role === 'admin') {
            return route('admin.support.show', $this->ticket->id);
        }

        if ($role === 'vendor') {
            return route('vendor.support.show', $this->ticket->id);
        }

        if ($role === 'influencer') {
            return route('influencer.support.show', $this->ticket->id);
        }

        return route('support.show', $this->ticket->reference_code);
    }
}
