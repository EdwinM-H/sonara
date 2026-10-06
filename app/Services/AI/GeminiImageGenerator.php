<?php

namespace App\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Genera la imagen del anuncio con Google Gemini (Interactions API) y la
 * guarda en public/uploads/emprendimientos.
 *
 *   POST https://generativelanguage.googleapis.com/v1beta/interactions
 *   x-goog-api-key: GEMINI_API_KEY
 *   {"model": "gemini-3.1-flash-lite-image", "input": "<prompt>",
 *    "response_format": {"type": "image", "aspect_ratio": "9:16"}}
 *
 * La imagen llega en base64 dentro de steps[].content[] con type "image"
 * (en la práctica, image/jpeg). La clave va en la cabecera, no en la URL,
 * para que nunca termine en un mensaje de error ni en un log.
 */
class GeminiImageGenerator
{
    public const DIRECTORY = 'uploads/emprendimientos';

    /** Vertical, como un flyer para compartir por WhatsApp o redes. */
    public const ASPECT_RATIO = '9:16';

    protected const EXTENSIONS = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];

    /** Carpeta dentro de public/; los tests usan otra para no pisar los flyers reales. */
    public static function directory(): string
    {
        return trim(config('services.gemini.directory') ?: self::DIRECTORY, '/');
    }

    public static function isConfigured(): bool
    {
        return filled(config('services.gemini.api_key'));
    }

    /** @return string ruta relativa a public/ (para asset()), p. ej. uploads/emprendimientos/12.jpg */
    public function generate(string $prompt, string $filename): string
    {
        $apiKey = config('services.gemini.api_key');
        if (blank($apiKey)) {
            throw new RuntimeException('Falta GEMINI_API_KEY en el entorno.');
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->timeout(config('services.gemini.timeout', 90))
                ->post(config('services.gemini.endpoint'), [
                    'model' => config('services.gemini.image_model'),
                    'input' => $prompt,
                    'response_format' => ['type' => 'image', 'aspect_ratio' => self::ASPECT_RATIO],
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('No se pudo conectar con Gemini (tiempo de espera o red).');
        }

        if ($response->failed()) {
            $detail = $response->json('error.message');
            throw new RuntimeException('Gemini respondió HTTP '.$response->status().($detail ? ': '.mb_substr($detail, 0, 200) : '.'));
        }

        $image = self::findImage($response->json() ?? []);
        if (! $image) {
            throw new RuntimeException('La API no devolvió imagen. Revisar el prompt o el modelo.');
        }

        $bytes = base64_decode($image['data'], true);
        $extension = self::EXTENSIONS[$image['mime_type'] ?? ''] ?? null;
        if ($bytes === false || ! $extension || ! @getimagesizefromstring($bytes)) {
            throw new RuntimeException('Gemini devolvió una imagen inválida.');
        }

        File::ensureDirectoryExists(public_path(self::directory()));
        $name = preg_replace('/[^A-Za-z0-9_-]/', '', $filename) ?: 'imagen';
        foreach (self::EXTENSIONS as $ext) {
            File::delete(public_path(self::directory()."/{$name}.{$ext}")); // al reintentar, sin restos de otro formato
        }
        $path = self::directory()."/{$name}.{$extension}";
        File::put(public_path($path), $bytes);

        return $path;
    }

    /**
     * Primer contenido de tipo imagen de la respuesta. También acepta
     * "output_image" (el atajo que expone el SDK oficial).
     *
     * @return ?array{data: string, mime_type?: string}
     */
    protected static function findImage(array $json): ?array
    {
        if (isset($json['output_image']['data'])) {
            return $json['output_image'];
        }
        foreach ($json['steps'] ?? [] as $step) {
            foreach ($step['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'image' && ! empty($content['data'])) {
                    return $content;
                }
            }
        }

        return null;
    }
}
