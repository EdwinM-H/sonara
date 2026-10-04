<?php

namespace App\Services\Publishing;

use App\Services\AI\GeminiImageGenerator;
use App\Services\AI\ImageGenerationService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Obtiene la imagen del anuncio. Según la configuración, en este orden:
 *
 * 1. GEMINI_API_KEY → Google Gemini; la imagen se guarda en
 *    public/uploads/emprendimientos/{nombre}.{ext}.
 * 2. IMAGE_SERVER_URL → servidor externo:
 *    POST {"prompt": "..."} (Bearer IMAGE_SERVER_API_KEY si hay clave)
 *    → JSON con la URL en "url", "image_url", "data.url" o "data.0.url".
 * 3. Ninguno → proveedor local de AI_PROVIDER (mock, pollinations...).
 */
class ImageServerClient
{
    protected const URL_KEYS = ['url', 'image_url', 'imageUrl', 'data.url', 'data.image_url', 'data.0.url'];

    public function __construct(
        protected ImageGenerationService $localImages,
        protected GeminiImageGenerator $gemini,
    ) {
    }

    /** @return string URL absoluta de la imagen */
    public function generate(string $prompt, string $filename = 'anuncio'): string
    {
        if (GeminiImageGenerator::isConfigured()) {
            return asset($this->gemini->generate($prompt, $filename));
        }

        $endpoint = config('services.image_server.url');
        if (! $endpoint) {
            return $this->generateLocally($prompt);
        }

        $response = Http::acceptJson()
            ->timeout(config('services.image_server.timeout', 120))
            ->when(config('services.image_server.api_key'), fn ($http, $key) => $http->withToken($key))
            ->post($endpoint, ['prompt' => $prompt]);

        if ($response->failed()) {
            throw new RuntimeException('El servidor de imágenes respondió HTTP '.$response->status().'.');
        }

        $json = $response->json() ?? [];
        foreach (self::URL_KEYS as $key) {
            $url = Arr::get($json, $key);
            if (is_string($url) && self::isHttpUrl($url)) {
                return $url;
            }
        }

        throw new RuntimeException('El servidor de imágenes no devolvió una URL de imagen válida.');
    }

    protected function generateLocally(string $prompt): string
    {
        $path = $this->localImages->provider()->generate($prompt)['image_path'];

        return self::isHttpUrl($path) ? $path : asset($path);
    }

    public static function isHttpUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);
    }
}
