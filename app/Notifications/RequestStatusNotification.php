<?php

namespace App\Notifications;

use App\Models\Request as CustomerRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestStatusNotification extends Notification
{
    use Queueable;

    public function __construct(public CustomerRequest $request)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        $labels = [
            'aceptada' => 'aceptada',
            'rechazada' => 'rechazada',
            'completada' => 'completada',
            'cancelada' => 'cancelada',
        ];

        return [
            'title' => 'Estado de tu solicitud actualizado',
            'message' => 'Tu solicitud '.$this->request->code.' fue '.($labels[$this->request->status] ?? $this->request->status).'.',
            'request_id' => $this->request->id,
            'request_code' => $this->request->code,
            'url' => route('customer.requests.show', $this->request),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject('SONARA · '.$data['title'])
            ->greeting('Hola, '.$notifiable->first_name.':')
            ->line($data['message'])
            ->action('Ver mi solicitud', $data['url'])
            ->line('Este es un mensaje automático de SONARA.');
    }
}
