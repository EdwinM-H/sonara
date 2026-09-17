<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Proveedor de generación de imágenes usando Pollinations.ai.
 *
 * Gratuito, sin API key, sin facturación ni cuenta: es una simple
 * petición HTTP GET a un endpoint público que devuelve la imagen
 * generada (modelo de difusión Flux) directamente en el cuerpo de la
 * respuesta. Pensado como proveedor por defecto para demostración
 * académica sin depender de tarjetas de crédito ni cuotas de pago.
 *
 * Notas de comportamiento descubiertas probando en vivo contra el
 * modelo real (no son suposiciones):
 *  - `enhance=true` (deja que una IA de Pollinations reescriba el
 *    prompt antes de generar) mejora mucho la relevancia del resultado.
 *  - Mencionar "texto"/"logo"/"marca de agua" en el prompt —incluso en
 *    negativo ("sin texto")— hace que el modelo intente dibujar letras
 *    y las dibuja mal (garabatos). Por eso este proveedor NO reutiliza
 *    el prompt largo e instructivo de PromptBuilder tal cual: lo destila
 *    a una descripción de escena corta y puramente visual, sin mencionar
 *    nunca la palabra texto/logo/marca de agua ni encuadrarlo como
 *    "flyer" o "anuncio" (esas palabras predisponen al modelo a intentar
 *    componer tipografía).
 *  - `nologo=true` no elimina la marca de agua para uso anónimo (solo
 *    con una cuenta/"referrer" registrado en auth.pollinations.ai);
 *    documentado así para no prometer algo que el nivel gratuito no da.
 */
class PollinationsImageProvider implements ImageGenerationProvider
{
    /** Categorías de SONARA → descriptor visual conciso en inglés. */
    private const CATEGORY_VISUALS = [
        'Gastronomía' => 'gourmet food photography, appetizing dish, restaurant kitchen',
        'Artesanía' => 'handmade crafts, artisan workshop, handwoven textures',
        'Belleza' => 'beauty salon, cosmetics, elegant styling, soft skin',
        'Textiles' => 'handwoven textiles, colorful yarn, traditional weaving',
        'Tecnología' => 'modern technology workspace, electronics, computer devices',
        'Servicios profesionales' => 'professional office, business consulting, modern workspace',
        'Educación' => 'education, classroom, books, learning materials',
        'Masajes' => 'relaxing spa massage room, wellness, candles, towels',
        'Limpieza' => 'professional cleaning service, spotless tidy home',
        'Repostería' => 'bakery pastries, fresh desserts, cakes, sweet treats',
        'Venta de productos' => 'retail products display, small shop, merchandise',
        'Otros' => 'small local business, entrepreneurship, craftsmanship',
    ];

    public function generate(string $prompt, string $style = null): array
    {
        $visualPrompt = $this->distillForDiffusion($prompt, $style);

        $encoded = rawurlencode(Str::limit($visualPrompt, 300, ''));
        $seed = random_int(1, 999999);

        $url = "https://image.pollinations.ai/prompt/{$encoded}"
            .'?width=1024&height=1024&model=flux&enhance=true&safe=true&seed='.$seed;

        $response = Http::timeout(90)->get($url);

        if ($response->failed()) {
            Log::error('Pollinations image generation failed', [
                'status' => $response->status(),
            ]);

            throw new \RuntimeException('El proveedor de IA (Pollinations) no pudo generar la imagen (HTTP '.$response->status().').');
        }

        $image = $response->body();
        $contentType = $response->header('Content-Type', 'image/jpeg');

        if (blank($image) || ! str_starts_with($contentType, 'image/')) {
            Log::error('Pollinations no devolvió una imagen válida', ['content_type' => $contentType]);

            throw new \RuntimeException('El proveedor de IA (Pollinations) no devolvió una imagen válida.');
        }

        $extension = match (true) {
            str_contains($contentType, 'png') => 'png',
            str_contains($contentType, 'webp') => 'webp',
            default => 'jpg',
        };

        $filename = 'flyers/'.date('Y/m/d').'/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->put($filename, $image);

        return [
            'image_path' => 'storage/'.$filename,
            'reference' => $visualPrompt,
            'cost' => 0.0,
        ];
    }

    /**
     * Convierte el prompt largo e instructivo compartido (pensado para
     * modelos tipo GPT/Gemini) en una descripción de escena corta y
     * puramente visual, apta para un modelo de difusión anónimo.
     */
    private function distillForDiffusion(string $prompt, ?string $style): string
    {
        $category = null;
        if (preg_match('/\(([^)]+)\)\./u', $prompt, $m)) {
            $category = trim($m[1]);
        }

        $visual = self::CATEGORY_VISUALS[$category] ?? 'small local business, professional setting';

        $parts = array_filter([
            $visual,
            $style,
            'warm inviting mood, soft natural lighting, professional photography',
        ]);

        return implode(', ', $parts);
    }

    public function name(): string
    {
        return 'pollinations';
    }
}
