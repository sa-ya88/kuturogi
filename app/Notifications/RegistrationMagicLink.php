<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class RegistrationMagicLink extends Notification
{
    use Queueable;

    protected string $email;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $email)
    {
        $this->email = $email;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'register.details',
            Carbon::now()->addMinutes(60),
            ['email' => $this->email]
        );

        return (new MailMessage)
            ->subject('【くつろぎ】新規会員登録のご案内')
            ->line('くつろぎへの会員登録をご検討いただき、ありがとうございます。')
            ->line('以下のボタンをクリックして、会員登録手続きを完了させてください。')
            ->action('会員登録を完了する', $url)
            ->line('このリンクの有効期限は60分です。')
            ->line('心当たりがない場合は、このメールを破棄してください。');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
