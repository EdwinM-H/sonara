<?php

namespace App\Notifications;

use App\Models\Request as CustomerRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCustomerRequestNotification extends Notification
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
        return [
            'title' => 'Nueva solicitud recibida',
            'message' => 'Has recibido una nueva solicitud: '.$this->request->item_name.' ('.$this->request->customer_name.').',
            'request_id' => $this->request->id,
            'request_code' => $this->request->code,
            'url' => route('entrepreneur.requests.show', $this->request),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject('SONARA · '.$data['title'])
            ->greeting('Hola, '.$notifiable->first_name.':')
            ->line($data['message'])
            ->line('Código de solicitud: '.$data['request_code'])
            ->action('Ver solicitud', $data['url'])
            ->line('Este es un mensaje automático de SONARA.');
    }
}
