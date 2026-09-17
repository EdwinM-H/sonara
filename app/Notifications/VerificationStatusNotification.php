<?php

namespace App\Notifications;

use App\Models\EntrepreneurProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationStatusNotification extends Notification
{
    use Queueable;

    public function __construct(public EntrepreneurProfile $profile, public string $status)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        $messages = [
            'document_pending' => 'Recuerda: tienes un plazo para cargar tu documento de acreditación.',
            'document_received' => 'Tu documento fue recibido. Está en revisión.',
            'approved' => '¡Felicitaciones! Tu emprendimiento fue verificado.',
            'rejected' => 'Tu documentación fue rechazada. Revisa el motivo e inténtalo nuevamente.',
            'deadline' => 'Estás a pocos días del vencimiento de tu documentación.',
        ];

        return [
            'title' => 'Estado de verificación',
            'message' => $messages[$this->status] ?? $this->status,
            'url' => route('entrepreneur.documents.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject('SONARA · '.$data['title'])
            ->greeting('Hola, '.$notifiable->first_name.':')
            ->line($data['message'])
            ->action('Ver mi documentación', $data['url'])
            ->line('Este es un mensaje automático de SONARA.');
    }
}
