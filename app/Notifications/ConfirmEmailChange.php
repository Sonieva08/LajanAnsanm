<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmEmailChange extends Notification
{
    use Queueable;

    public function __construct(private readonly string $url) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Confirm your new email address'))
            ->line(__('A request was made to change the email address on your LajanAnsanm account.'))
            ->action(__('Confirm email address'), $this->url)
            ->line(__('This confirmation link expires in 60 minutes. If you did not request this change, you can ignore this email.'));
    }
}
