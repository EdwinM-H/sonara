<?php

namespace App\Notifications;

use App\Models\AssistanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssistanceRequestNotification extends Notification
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
        return [
            'title' => 'Nueva solicitud de asistencia',
            'message' => $this->assistance->user->name.' solicitó ayuda: '.$this->assistance->subject,
            'url' => route('admin.assistance.show', $this->assistance),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject('SONARA · '.$data['title'])
            ->greeting('Hola, '.$notifiable->first_name.':')
            ->line($data['message'])
            ->action('Ver solicitud de asistencia', $data['url'])
            ->line('Este es un mensaje automático de SONARA.');
    }
}
