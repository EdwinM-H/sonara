<?php

namespace App\Services\Publishing;

use App\Models\Business;

/**
 * Prompt en inglés para la imagen del anuncio, armado solo con los datos
 * que el emprendedor registró. Pide una imagen sin texto: los modelos
 * dibujan letras de forma poco fiable y así no pueden inventar precios ni
 * contactos dentro de la imagen (el muro ya los muestra como texto).
 */
class AdPromptBuilder
{
    public function build(Business $business): string
    {
        $tags = $business->tags ?? [];

        return implode("\n", [
            'Create a professional and vibrant advertisement image for a business called "'.$business->name.'".',
            'Category: '.($business->categoryLabel() ?: 'general').'. Sector: '.($business->sector ?: 'local')
                .'. Location: '.($business->address ?: 'not specified').'.',
            'Description: '.rtrim((string) $business->description, '.').'.',
            'Price range: '.($business->price_text ?: 'to be consulted').'.',
            'Hours: '.($business->schedule_text ?: 'available on request').'.',
            'Keywords: '.($tags ? implode(', ', $tags) : 'none').'.',
            'Style: modern, clean, eye-catching, suitable for a business directory listing.',
            'Do not include any text or logos in the image.',
        ]);
    }
}
