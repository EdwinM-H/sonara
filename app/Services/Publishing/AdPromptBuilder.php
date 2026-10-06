<?php

namespace App\Services\Publishing;

use App\Models\Business;

/**
 * Prompt en inglés para el flyer del anuncio, armado solo con los datos que
 * el emprendedor registró. El flyer lleva esos datos como texto (nombre,
 * precio, horario, WhatsApp) y se pide al modelo que no invente nada más.
 * Los valores por defecto van en español porque se imprimen en el flyer.
 */
class AdPromptBuilder
{
    public function build(Business $business): string
    {
        $tags = $business->tags ?? [];
        $category = $business->categoryLabel() ?: 'general';
        $whatsapp = $business->whatsapp ?: $business->phone;

        $info = array_filter([
            '- Business name: "'.$business->name.'" (make this the most prominent text, large and bold)',
            '- Category: '.$category,
            '- Sector / Zone: '.($business->sector ?: 'local').' — '.($business->address ?: 'not specified'),
            '- Description: "'.rtrim((string) $business->description, '.').'" (include as a short tagline or subtitle)',
            '- Price: '.($business->price_text ?: 'Consultar precio'),
            '- Hours: '.($business->schedule_text ?: 'Consultar horario'),
            $whatsapp ? '- WhatsApp: '.$whatsapp : null,
            $tags ? '- Keywords / tags: '.implode(', ', $tags) : null,
        ]);

        return implode("\n", [
            'Design a professional, eye-catching advertising flyer for a business.',
            '',
            'BUSINESS INFORMATION TO INCLUDE IN THE FLYER:',
            ...$info,
            '',
            'DESIGN REQUIREMENTS:',
            '- Style: modern, vibrant, professional — like a real printed or digital flyer',
            '- Layout: structured with clear visual hierarchy (title at top, details in middle, contact at bottom)',
            '- Include decorative design elements, shapes, or backgrounds that match the category "'.$category.'"',
            '- Use bold colors and clean typography',
            $whatsapp ? '- Add a subtle "WhatsApp" label next to the phone number' : '- Do not show any phone number',
            '- Make it look like it was designed by a professional graphic designer',
            '- Format: vertical/portrait orientation, suitable for sharing on social media or WhatsApp',
            '- Do NOT add any placeholder text, watermarks, or lorem ipsum',
            '- All text in the flyer must be exactly as provided above, no invented information',
        ]);
    }
}
