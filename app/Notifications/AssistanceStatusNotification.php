<?php

namespace App\Notifications;

use App\Models\AssistanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssistanceStatusNotification extends Notification
{
    use Queueable;

    public function __construct(public AssistanceRequest $assistance)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        $labels = [
            'en_atencion' => 'está siendo atendida',
            'atendida' => 'fue atendida',
            'cerrada' => 'fue cerrada',
        ];

        return [
            'title' => 'Solicitud de asistencia',
            'message' => 'Tu solicitud de asistencia '.($labels[$this->assistance->status] ?? 'fue actualizada').'.',
            'url' => route('entrepreneur.assistance.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject('SONARA · '.$data['title'])
            ->greeting('Hola, '.$notifiable->first_name.':')
            ->line($data['message'])
            ->action('Ver mis solicitudes de asistencia', $data['url'])
            ->line('Este es un mensaje automático de SONARA.');
    }
}
