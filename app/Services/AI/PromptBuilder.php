<?php

namespace App\Services\AI;

use App\Models\AIGeneration;
use App\Models\Publication;

/**
 * Construye el prompt de la IA EXCLUSIVAMENTE a partir de datos
 * estructurados almacenados en la base de datos.
 *
 * La IA NUNCA puede inventar precios, horarios, ubicación ni contactos:
 * todo proviene de aquí.
 */
class PromptBuilder
{
    /**
     * IMPORTANTE: la imagen generada se usa como fondo/portada visual;
     * el nombre, precio, ubicación, etc. SONARA ya los muestra como texto
     * real (HTML) superpuesto en las tarjetas y la página de detalle.
     * Por eso el prompt pide explícitamente una imagen SIN texto: los
     * modelos de generación de imágenes renderizan letras de forma poco
     * fiable (palabras ilegibles o inventadas), y pedir texto legible con
     * datos comerciales exactos es, en la práctica, imposible de
     * garantizar. Esto además refuerza la regla de que la IA nunca es
     * fuente de verdad comercial: si no dibuja texto, no puede inventar
     * precios, horarios ni contactos dentro de la imagen.
     */
    public function build(Publication $publication, array $extra = []): string
    {
        $business = $publication->business;
        $category = $business->category->name ?? 'general business';
        $description = $publication->description ?: $business->description;
        $style = $extra['style'] ?? null;

        // Prompt corto, concreto y encabezado en inglés: los modelos de
        // generación de imágenes (incluido el backend gratuito de
        // Pollinations) siguen mejor descripciones visuales breves en
        // inglés que párrafos largos de instrucciones en español, y con
        // esto la relevancia del resultado mejora notablemente.
        $lines = [
            'Professional editorial photography for a business flyer background image.',
            'No text, no letters, no numbers, no logos, no watermarks, no captions anywhere in the image.',
            "Subject: {$publication->name} ({$category}). {$description}",
        ];

        if (! empty($style)) {
            $lines[] = "Visual mood/style: {$style}.";
        }

        $lines[] = 'Focus on the product, service, environment or setting itself (not a generic portrait).';
        $lines[] = 'Clean composition, soft natural lighting, high contrast, professional and inviting.';
        $lines[] = 'Do not invent or depict specific prices, schedules, addresses or phone numbers.';

        return implode("\n", $lines);
    }

    public function forRegeneration(AIGeneration $generation, string $style = null): string
    {
        $publication = $generation->publication ?: Publication::find($generation->publication_id);

        return $this->build($publication, ['style' => $style ?? $generation->style]);
    }
}