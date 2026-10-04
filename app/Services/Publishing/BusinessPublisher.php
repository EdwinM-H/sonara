<?php

namespace App\Services\Publishing;

use App\Models\Business;
use Illuminate\Support\Facades\Log;

/**
 * Al terminar el registro de un emprendimiento: arma el prompt (A), genera
 * la imagen (B) y publica el anuncio en la web externa (C).
 *
 * Si la imagen falla, el emprendimiento se publica igual sin imagen
 * (image_url = null, image_pending = true) y retryImage() la reintenta
 * después. Si falla la web externa, queda publish_status = error.
 */
class BusinessPublisher
{
    public function __construct(
        protected AdPromptBuilder $prompts,
        protected ImageServerClient $images,
        protected PublishWebClient $web,
    ) {
    }

    /** @return array{image: bool, published: bool} */
    public function publish(Business $business): array
    {
        $business->update([
            'image_prompt' => $this->prompts->build($business),
            'publish_status' => Business::PUBLISH_PENDIENTE,
            'publish_error' => null,
        ]);

        $image = $business->image_url ? true : $this->generateImage($business);

        // La tarjeta del muro pasa del marcador de posición a la imagen.
        $business->ensureAdPublication();

        try {
            $ad = $this->web->publish($business);
            $business->update([
                'external_ad_id' => $ad['id'],
                'external_ad_url' => $ad['url'],
                'publish_status' => Business::PUBLISH_PUBLICADO,
                'published_at' => now(),
            ]);

            return ['image' => $image, 'published' => true];
        } catch (\Throwable $e) {
            $this->fail($business, 'publish_error', 'No se pudo publicar el anuncio del emprendimiento', $e);
            $business->update(['publish_status' => Business::PUBLISH_ERROR]);

            return ['image' => $image, 'published' => false];
        }
    }

    /** Reintenta la imagen de un emprendimiento que quedó pendiente. */
    public function retryImage(Business $business): bool
    {
        if (! $business->image_prompt) {
            $business->update(['image_prompt' => $this->prompts->build($business)]);
        }
        if (! $this->generateImage($business)) {
            return false;
        }
        $business->ensureAdPublication(); // la tarjeta toma la imagen nueva

        return true;
    }

    protected function generateImage(Business $business): bool
    {
        try {
            $business->update([
                'image_url' => $this->images->generate($business->image_prompt, 'emprendimiento-'.$business->id),
                'image_pending' => false,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->fail($business, null, 'No se pudo generar la imagen del emprendimiento', $e);
            $business->update(['image_url' => null, 'image_pending' => true]);

            return false;
        }
    }

    protected function fail(Business $business, ?string $column, string $message, \Throwable $e): void
    {
        // Solo el mensaje de la excepción: las claves viajan en cabeceras,
        // nunca en URLs ni en estos mensajes.
        Log::error($message, ['business_id' => $business->id, 'error' => $e->getMessage()]);
        if ($column) {
            $business->update([$column => mb_substr($e->getMessage(), 0, 1000)]);
        }
    }
}
