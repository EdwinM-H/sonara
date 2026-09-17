<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OpenAIImageProvider implements ImageGenerationProvider
{
    public function generate(string $prompt, string $style = null): array
    {
        $apiKey = config('services.ai.api_key');
        $model = config('services.ai.model', 'gpt-image-1');
        $size = config('services.ai.size', '1024x1024');

        if (blank($apiKey)) {
            throw new \RuntimeException('La API key de IA no está configurada (AI_API_KEY).');
        }

        $response = Http::withToken($apiKey)
            ->timeout(120)
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => $model,
                'prompt' => $prompt,
                'n' => 1,
                'size' => $size,
            ]);

        if ($response->failed()) {
            Log::error('OpenAI image generation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('El proveedor de IA no pudo generar la imagen (HTTP '.$response->status().').');
        }

        $data = $response->json();
        $b64 = data_get($data, 'data.0.b64_json');
        $url = data_get($data, 'data.0.url');
        $revised = data_get($data, 'data.0.revised_prompt');
        $cost = data_get($data, 'usage.total_cost');

        $filename = 'flyers/'.date('Y/m/d').'/'.Str::uuid().'.png';
        $image = $b64
            ? base64_decode($b64, true)
            : ($url ? Http::timeout(120)->get($url)->body() : null);

        if ($image === false || $image === null) {
            throw new \RuntimeException('El proveedor de IA no devolvió una imagen válida.');
        }

        Storage::disk('public')->put($filename, $image);

        return [
            'image_path' => 'storage/'.$filename,
            'reference' => $revised,
            'cost' => $cost !== null ? (float) $cost : null,
        ];
    }

    public function name(): string
    {
        return 'openai';
    }
}