<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Proveedor de generación de imágenes usando la API de Google AI Studio
 * (Gemini, capacidad de salida de imagen). Gratuito dentro de la cuota
 * diaria de la cuenta de AI Studio — no requiere tarjeta de crédito.
 *
 * Configuración (.env):
 *   AI_PROVIDER=google
 *   AI_API_KEY=AIza...            (obtenida en aistudio.google.com)
 *   AI_GOOGLE_MODEL=gemini-2.5-flash-image   (opcional, tiene valor por defecto)
 */
class GoogleImageProvider implements ImageGenerationProvider
{
    public function generate(string $prompt, string $style = null): array
    {
        $apiKey = config('services.ai.api_key');
        $model = config('services.ai.google_model', 'gemini-2.5-flash-image');

        if (blank($apiKey)) {
            throw new \RuntimeException('La API key de Google AI Studio no está configurada (AI_API_KEY).');
        }

        $fullPrompt = $prompt;
        if (! empty($style)) {
            $fullPrompt .= "\n\nEstilo visual: {$style}";
        }

        $response = Http::timeout(120)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $fullPrompt]]],
                ],
                'generationConfig' => [
                    'responseModalities' => ['IMAGE', 'TEXT'],
                ],
            ]);

        if ($response->failed()) {
            Log::error('Google (Gemini) image generation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('El proveedor de IA (Google) no pudo generar la imagen (HTTP '.$response->status().').');
        }

        $data = $response->json();
        $parts = data_get($data, 'candidates.0.content.parts', []);

        $imageData = null;
        $mimeType = 'image/png';
        $textNote = null;

        foreach ($parts as $part) {
            if (isset($part['inlineData']['data'])) {
                $imageData = $part['inlineData']['data'];
                $mimeType = $part['inlineData']['mimeType'] ?? 'image/png';
            } elseif (isset($part['text'])) {
                $textNote = $part['text'];
            }
        }

        if (! $imageData) {
            Log::error('Google (Gemini) no devolvió una imagen', ['body' => $data]);

            throw new \RuntimeException(
                'El modelo de Google no devolvió una imagen. '
                .($textNote ? 'Respuesta: '.Str::limit($textNote, 200) : 'Verifica que el modelo configurado soporte generación de imágenes.'),
            );
        }

        $image = base64_decode($imageData, true);
        if ($image === false) {
            throw new \RuntimeException('El proveedor de IA (Google) devolvió una imagen inválida.');
        }

        $extension = match (true) {
            str_contains($mimeType, 'jpeg'), str_contains($mimeType, 'jpg') => 'jpg',
            str_contains($mimeType, 'webp') => 'webp',
            default => 'png',
        };

        $filename = 'flyers/'.date('Y/m/d').'/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->put($filename, $image);

        return [
            'image_path' => 'storage/'.$filename,
            'reference' => $textNote,
            'cost' => null,
        ];
    }

    public function name(): string
    {
        return 'google';
    }
}
