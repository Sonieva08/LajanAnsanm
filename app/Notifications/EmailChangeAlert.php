<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailChangeAlert extends Notification
{
    use Queueable;

    public function __construct(private readonly string $newEmail) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Email address changed'))
            ->line(__('The email address on your LajanAnsanm account was changed.'))
            ->line(__('New email address: :email', ['email' => $this->newEmail]))
            ->line(__('If you did not make this change, reset your password immediately and contact support.'));
    }
}
