<?php

namespace App\Notifications;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Avisa al admin que un emprendimiento se registró con valores fuera de
 * catálogo porque ese catálogo estaba vacío, para que cree las opciones.
 */
class CatalogFreeValueNotification extends Notification
{
    use Queueable;

    /** @param array<string, string> $values campo => valor dictado */
    public function __construct(public Business $business, public array $values)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $detail = collect($this->values)->map(fn ($value, $field) => $field.': "'.$value.'"')->implode(', ');

        return [
            'title' => 'Valor libre en un catálogo vacío',
            'message' => 'El emprendimiento "'.$this->business->name.'" se registró con '.$detail
                .' porque el catálogo no tenía opciones. Revisa los catálogos.',
            'url' => route('admin.catalogs.index'),
        ];
    }
}
