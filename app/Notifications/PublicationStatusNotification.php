<?php

namespace App\Notifications;

use App\Models\Publication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PublicationStatusNotification extends Notification
{
    use Queueable;

    public function __construct(public Publication $publication, public string $status)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        $messages = [
            'publicada' => 'Tu publicación "'.$this->publication->name.'" fue publicada.',
            'rechazada' => 'Tu publicación "'.$this->publication->name.'" fue rechazada.',
            'pendiente' => 'Tu publicación "'.$this->publication->name.'" fue enviada a revisión.',
            'suspendida' => 'Tu publicación "'.$this->publication->name.'" fue suspendida.',
        ];

        return [
            'title' => 'Estado de publicación',
            'message' => $messages[$this->status] ?? $this->status,
            'url' => route('entrepreneur.publications.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject('SONARA · '.$data['title'])
            ->greeting('Hola, '.$notifiable->first_name.':')
            ->line($data['message'])
            ->action('Ver mis publicaciones', $data['url'])
            ->line('Este es un mensaje automático de SONARA.');
    }
}
