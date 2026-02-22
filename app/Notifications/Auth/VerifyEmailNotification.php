<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends BaseVerifyEmail
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);
        $appName = (string) config('app.name', 'Daily Calisthenics');
        $fromAddress = (string) (config('mail.from.address') ?: 'no-reply@example.com');
        $fromName = (string) (config('mail.from.name') ?: $appName);
        $resendKey = (string) config('services.resend.key', '');
        $mailer = $resendKey !== '' ? 'resend' : (string) config('mail.default', 'log');

        return (new MailMessage)
            ->mailer($mailer)
            ->from($fromAddress, $fromName)
            ->subject("Verify your {$appName} email")
            ->line('Click the button below to verify your email address.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('If you did not create an account, no further action is required.');
    }
}
