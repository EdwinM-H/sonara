<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Proveedor MOCK para desarrollo y demostración.
 *
 * Genera una imagen SVG descriptiva a partir del prompt, de modo que el
 * flujo completo (interfaz, base de datos, historial, límites) puede
 * demostrarse sin requerir una API key real.
 *
 * IMPORTANTE: este modo NO reemplaza la integración real. Configura
 * AI_PROVIDER=openai y AI_API_KEY para producción.
 */
class MockImageProvider implements ImageGenerationProvider
{
    public function generate(string $prompt, string $style = null): array
    {
        $safe = Str::limit($prompt, 80);
        $colors = ['#6D28D9', '#7C3AED', '#4C1D95', '#9333EA'];
        $color = $colors[random_int(0, count($colors) - 1)];
        $styleLabel = $style ? ucfirst($style) : 'Moderno';

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1024" height="1024" viewBox="0 0 1024 1024">
  <rect width="1024" height="1024" fill="#F9F6F1"/>
  <rect x="40" y="40" width="944" height="944" rx="48" fill="#FFFFFF" stroke="{$color}" stroke-width="6"/>
  <circle cx="512" cy="360" r="140" fill="{$color}" opacity="0.15"/>
  <circle cx="512" cy="360" r="88" fill="{$color}"/>
  <path d="M472 360 l28 28 l56 -56" stroke="#FFFFFF" stroke-width="14" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
  <text x="512" y="600" text-anchor="middle" font-family="Arial, sans-serif" font-size="54" font-weight="bold" fill="#1F2937">SONARA</text>
  <text x="512" y="660" text-anchor="middle" font-family="Arial, sans-serif" font-size="30" fill="#6B7280">{$styleLabel}</text>
  <text x="512" y="760" text-anchor="middle" font-family="Arial, sans-serif" font-size="24" fill="#9CA3AF">{$safe}</text>
</svg>
SVG;

        $filename = 'flyers/'.date('Y/m/d').'/'.Str::uuid().'.svg';
        Storage::disk('public')->put($filename, $svg);

        Log::info('MockImageProvider generó una imagen (modo desarrollo).', ['filename' => $filename]);

        return [
            'image_path' => 'storage/'.$filename,
            'reference' => null,
            'cost' => 0.0,
        ];
    }

    public function name(): string
    {
        return 'mock';
    }
}