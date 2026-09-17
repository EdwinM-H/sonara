<?php

namespace App\Services\Voice;

/**
 * Proveedor que delega la síntesis de voz a la API nativa del navegador
 * (SpeechSynthesis). El cliente reproduce el audio; este proveedor queda
 * disponible como configuración para integrar proveedores externos.
 */
class BrowserTextToSpeechProvider implements TextToSpeechProvider
{
    public function speak(string $text): array
    {
        return ['mode' => 'browser', 'text' => $text];
    }

    public function name(): string
    {
        return 'browser';
    }
}