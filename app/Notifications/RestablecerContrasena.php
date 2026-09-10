<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Correo de recuperación de contraseña en español.
 */
class RestablecerContrasena extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);
        $minutos = config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Restablece tu contraseña · SIGAM')
            ->greeting('Hola')
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta en SIGAM.')
            ->action('Restablecer contraseña', $url)
            ->line("Este enlace caduca en {$minutos} minutos.")
            ->line('Si tú no hiciste esta solicitud, puedes ignorar este correo; tu contraseña no cambiará.')
            ->salutation('— Equipo SIGAM');
    }
}
