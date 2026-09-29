<?php

namespace App\Services\Assistant;

/**
 * Máquina de estados del login de emprendedor por voz: pide el usuario
 * (nombre completo) y luego el PIN de 4 dígitos. La verificación contra
 * la base de datos la hace VoiceLoginAuthenticator cuando process()
 * devuelve el tipo "ready".
 */
class VoiceLoginService
{
    public const WELCOME = 'Bienvenido a la pantalla de login. ';

    /** @var array<string, string> campo => pregunta */
    public const STEPS = [
        'usuario' => '¿Cuál es su usuario?',
        'pin' => '¿Cuál es su PIN?',
    ];

    public function __construct(protected ?string $sessionKey = null)
    {
    }

    public function start(string $note = self::WELCOME): array
    {
        $session = ['step' => 'usuario', 'data' => []];
        $this->save($session);

        return $this->question($session, $note);
    }

    public function resetSession(): void
    {
        cache()->forget($this->key());
    }

    public function process(?string $transcript): array
    {
        $session = cache()->get($this->key());
        if (! $session) {
            return $this->start();
        }

        $normalized = VoiceText::normalize($transcript);
        $command = VoiceAssistantService::COMMANDS[$normalized] ?? null;

        if ($command === 'repeat') {
            return $this->question($session);
        }
        if ($command === 'help') {
            return $this->question($session, 'Su usuario es su nombre completo: nombres y apellidos. Hable después del pitido. ');
        }
        if ($command === 'back' || $command === 'exit') {
            return $this->start('');
        }

        if ($session['step'] === 'usuario') {
            if ($normalized === '' || ! VoiceText::isLettersOnly($normalized)) {
                return $this->question($session, 'Su usuario es su nombre completo, solo con letras. ');
            }
            $session['data']['usuario'] = $normalized;
            $session['step'] = 'pin';
            $this->save($session);

            return $this->question($session);
        }

        $pin = SpokenDigits::parseFourDigits((string) $transcript);
        if (! $pin) {
            return $this->question($session, 'El PIN debe tener cuatro dígitos. ');
        }

        return [
            'type' => 'ready',
            'usuario' => $session['data']['usuario'] ?? '',
            'pin' => $pin,
        ];
    }

    protected function question(array $session, string $note = ''): array
    {
        $prompt = self::STEPS[$session['step']];

        return [
            'type' => 'question',
            'field' => $session['step'],
            'prompt' => $prompt,
            'speak' => $note.$prompt,
        ];
    }

    protected function save(array $session): void
    {
        cache()->put($this->key(), $session, now()->addMinutes(10));
    }

    protected function key(): string
    {
        return $this->sessionKey ?? 'voice_login.'.session()->getId();
    }
}
