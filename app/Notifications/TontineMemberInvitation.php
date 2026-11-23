<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TontineMemberInvitation extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $url,
        private readonly string $tontineName,
        private readonly string $organizerName,
        private readonly string $memberName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Envitasyon pou antre nan :tontine sou LajanAnsanm', ['tontine' => $this->tontineName]))
            ->greeting(__('Bonjou :name', ['name' => $this->memberName]))
            ->line(__(':organizer envite w vin manm nan sòl :tontine an.', [
                'organizer' => $this->organizerName,
                'tontine' => $this->tontineName,
            ]))
            ->action(__('Gade epi aksepte envitasyon an'), $this->url)
            ->line(__('Pòtay manm ou an ap rete disponib pandan sòl la ouvè.'));
    }
}
