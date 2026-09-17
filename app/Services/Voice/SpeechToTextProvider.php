<?php

namespace App\Services\Voice;

interface SpeechToTextProvider
{
    /**
     * Convierte audio (base64, wav) a texto.
     *
     * @return array{text: string, confidence: ?float}
     */
    public function transcribe(string $audioBase64 = null, string $language = 'es-PE'): array;

    public function name(): string;
}