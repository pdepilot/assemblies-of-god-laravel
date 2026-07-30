<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

final class AdminResetPasswordNotification extends ResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        $expire = (int) config('auth.passwords.admins.expire', 60);

        return (new MailMessage)
            ->subject(Lang::get('Reset your :platform admin password', [
                'platform' => (string) config('identity.admin.brand_name', 'AGC IKENEGBU'),
            ]))
            ->greeting(Lang::get('Password reset request'))
            ->line(Lang::get('You are receiving this email because we received a password reset request for your administrator account.'))
            ->action(Lang::get('Reset Password'), $url)
            ->line(Lang::get('This password reset link will expire in :count minutes.', ['count' => $expire]))
            ->line(Lang::get('If you did not request a password reset, no further action is required.'));
    }

    protected function resetUrl($notifiable): string
    {
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
