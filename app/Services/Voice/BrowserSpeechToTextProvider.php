<?php

namespace App\Services\Voice;

/**
 * Proveedor que delega el reconocimiento de voz a la API nativa del
 * navegador (Web Speech API). El cliente JavaScript captura el audio
 * y el reconocimiento ocurre en el navegador; este proveedor queda
 * disponible como configuración backend y para futuros envíos.
 */
class BrowserSpeechToTextProvider implements SpeechToTextProvider
{
    public function transcribe(string $audioBase64 = null, string $language = 'es-PE'): array
    {
        throw new \LogicException('El reconocimiento del navegador se ejecuta en el cliente (Web Speech API).');
    }

    public function name(): string
    {
        return 'browser';
    }
}