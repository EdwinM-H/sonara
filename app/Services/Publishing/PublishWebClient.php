<?php

namespace App\Services\Publishing;

use App\Models\Business;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Publica el anuncio en la web externa.
 *
 * Contrato con PUBLISH_WEB_URL:
 *   POST payload() (Authorization: Bearer PUBLISH_WEB_API_KEY si hay clave)
 *   → JSON con el id del anuncio ("id" o "data.id") y/o su URL ("url" o "data.url").
 *
 * La web externa organiza el anuncio por categoría → sector → etiquetas;
 * por eso el payload los manda explícitos y además como "ruta".
 *
 * Sin PUBLISH_WEB_URL el anuncio queda publicado solo en el portal de
 * SONARA (la ficha pública de su publicación en el muro).
 */
class PublishWebClient
{
    /** @return array{id: ?string, url: ?string} */
    public function publish(Business $business): array
    {
        $endpoint = config('services.publish_web.url');
        if (! $endpoint) {
            return [
                'id' => 'sonara-'.$business->id,
                'url' => route('public.publication', $business->ensureAdPublication()->slug),
            ];
        }

        $response = Http::acceptJson()
            ->timeout(config('services.publish_web.timeout', 30))
            ->when(config('services.publish_web.api_key'), fn ($http, $key) => $http->withToken($key))
            ->post($endpoint, $this->payload($business));

        if ($response->failed()) {
            throw new RuntimeException('La web de publicación respondió HTTP '.$response->status().'.');
        }

        $json = $response->json() ?? [];
        $id = Arr::get($json, 'id', Arr::get($json, 'data.id'));
        $url = Arr::get($json, 'url', Arr::get($json, 'data.url'));
        $url = is_string($url) && ImageServerClient::isHttpUrl($url) ? $url : null;

        if ($id === null && $url === null) {
            throw new RuntimeException('La web de publicación no devolvió el id ni la URL del anuncio.');
        }

        return ['id' => $id !== null ? (string) $id : null, 'url' => $url];
    }

    public function payload(Business $business): array
    {
        $category = $business->categoryLabel();
        $tags = $business->tags ?? [];

        return [
            'referencia' => 'sonara-'.$business->id,
            'titulo' => $business->name,
            'descripcion' => $business->description,
            'categoria' => $category,
            'sector' => $business->sector,
            'etiquetas' => $tags,
            'ruta' => array_values(array_filter([$category, $business->sector])),
            'tipo' => $business->type,
            'precio' => $business->price_text,
            'horario' => $business->schedule_text,
            'telefono' => $business->phone,
            'ubicacion' => $business->address,
            'imagen_url' => $business->image_url,
            'emprendedor' => $business->entrepreneurProfile?->user?->name,
            'origen' => config('app.name'),
        ];
    }
}
