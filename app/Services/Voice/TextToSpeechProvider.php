<?php

namespace App\Services\Voice;

interface TextToSpeechProvider
{
    /**
     * Devuelve el audio TTS (o delega al cliente en el caso del navegador).
     *
     * @return array{mode: string, audio_base64?: string, voice?: string}
     */
    public function speak(string $text): array;

    public function name(): string;
}