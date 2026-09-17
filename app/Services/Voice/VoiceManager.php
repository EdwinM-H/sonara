<?php

namespace App\Services\Voice;

/**
 * Administra la configuración de los proveedores de voz (STT / TTS).
 *
 * En esta fase el reconocimiento y la síntesis se ejecutan en el
 * navegador (Web Speech API) como fallback universal; la arquitectura
 * permite conmutar a servicios externos (Google, Azure, AWS, ElevenLabs)
 * editando STT_PROVIDER / TTS_PROVIDER en el entorno.
 */
class VoiceManager
{
    public function __construct(
        protected array $sttProviders = [],
        protected array $ttsProviders = [],
    ) {
        if (empty($sttProviders)) {
            $this->sttProviders = ['browser' => new BrowserSpeechToTextProvider];
        }
        if (empty($ttsProviders)) {
            $this->ttsProviders = ['browser' => new BrowserTextToSpeechProvider];
        }
    }

    public function speechToTextProvider(): SpeechToTextProvider
    {
        $configured = strtolower((string) config('services.voice.stt_provider', 'browser'));

        return $this->sttProviders[$configured] ?? $this->sttProviders['browser'];
    }

    public function textToSpeechProvider(): TextToSpeechProvider
    {
        $configured = strtolower((string) config('services.voice.tts_provider', 'browser'));

        return $this->ttsProviders[$configured] ?? $this->ttsProviders['browser'];
    }

    /**
     * Configuración expuesta al cliente para el motor de voz.
     */
    public function clientConfig(): array
    {
        return [
            'stt' => [
                'provider' => $this->speechToTextProvider()->name(),
                'language' => 'es-PE',
            ],
            'tts' => [
                'provider' => $this->textToSpeechProvider()->name(),
                'voice' => 'es-ES',
            ],
        ];
    }
}