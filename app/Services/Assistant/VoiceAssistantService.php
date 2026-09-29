<?php

namespace App\Services\Assistant;

use App\Models\User;

/**
 * Máquina de estados del registro de emprendedor por voz.
 *
 * Pregunta los seis campos uno por uno (nombres, apellidos, sobre mí,
 * ubicación, WhatsApp y PIN). Cada respuesta se normaliza (minúsculas,
 * sin tildes ni caracteres especiales) y se valida antes de pasar al
 * siguiente campo. El progreso vive en caché por sesión para poder
 * retomarlo; la creación de la cuenta la hace el controlador cuando
 * process() devuelve el tipo "complete".
 */
class VoiceAssistantService
{
    public const INTRO = 'Bienvenido al registro de emprendedor. Le haré seis preguntas cortas. '
        .'Hable después del pitido. ';

    public const HELP = 'Responda cada pregunta después del pitido. '
        .'Puede decir: repetir, atrás o cancelar. ';

    /** @var array<string, string> campo => pregunta */
    public const STEPS = [
        'nombres' => '¿Cuáles son sus nombres?',
        'apellidos' => '¿Cuáles son sus apellidos?',
        'sobre_mi' => 'Cuénteme brevemente sobre usted.',
        'ubicacion' => '¿En qué ciudad o sector se encuentra?',
        'telefono_whatsapp' => '¿Cuál es su número de WhatsApp? Dígalo número por número.',
        'pin' => 'Cree un PIN de cuatro dígitos. Dígalo número por número.',
    ];

    /** @var array<string, string> palabra normalizada => comando */
    public const COMMANDS = [
        'repetir' => 'repeat',
        'atras' => 'back',
        'volver' => 'back',
        'ayuda' => 'help',
        'cancelar' => 'exit',
        'salir' => 'exit',
    ];

    public function __construct(protected ?string $sessionKey = null)
    {
    }

    public function start(): array
    {
        $session = ['step' => array_key_first(self::STEPS), 'data' => []];
        $this->save($session);

        return $this->question($session, self::INTRO);
    }

    public function resume(): array
    {
        $session = $this->load();

        return $session ? $this->question($session) : $this->start();
    }

    public function hasSession(): bool
    {
        return (bool) $this->load();
    }

    public function resetSession(): void
    {
        cache()->forget($this->key());
    }

    public function data(): array
    {
        return $this->load()['data'] ?? [];
    }

    public function process(?string $transcript): array
    {
        $session = $this->load();
        if (! $session) {
            return $this->start();
        }

        $normalized = VoiceText::normalize($transcript);

        switch (self::COMMANDS[$normalized] ?? null) {
            case 'repeat':
                return $this->question($session);
            case 'help':
                return $this->question($session, self::HELP);
            case 'exit':
                $this->resetSession();

                return [
                    'type' => 'exited',
                    'speak' => 'El registro fue cancelado. Puede empezar de nuevo cuando quiera.',
                ];
            case 'back':
                $previous = $this->previousStep($session['step']);
                if ($previous === null) {
                    return $this->question($session, 'Esta es la primera pregunta. ');
                }
                $session['step'] = $previous;
                $this->save($session);

                return $this->question($session);
        }

        $field = $session['step'];
        $result = $this->validate($field, (string) $transcript, $session['data']);
        if ($result['error']) {
            return $this->question($session, $result['error'].' ');
        }

        $session['data'][$field] = $result['value'];

        if ($result['restart_at'] ?? null) {
            $session['step'] = $result['restart_at'];
            $this->save($session);

            return $this->question($session, $result['note'].' ');
        }

        $next = $this->nextStep($field);
        if ($next === null) {
            $this->save($session);

            return ['type' => 'complete', 'data' => $session['data']];
        }

        $session['step'] = $next;
        $this->save($session);

        return $this->question($session);
    }

    /**
     * Vuelve a la pregunta de nombres, conservando lo demás. Se usa si al
     * crear la cuenta el nombre completo resulta estar ya registrado.
     */
    public function restartAtNames(string $note): array
    {
        $session = $this->load() ?? ['data' => []];
        $session['step'] = 'nombres';
        $this->save($session);

        return $this->question($session, $note.' ');
    }

    public static function usernameFor(string $nombres, string $apellidos): string
    {
        return VoiceText::normalize($nombres.' '.$apellidos);
    }

    /**
     * @return array{value: ?string, error: ?string, restart_at?: string, note?: string}
     */
    protected function validate(string $field, string $transcript, array $data): array
    {
        $normalized = VoiceText::normalize($transcript);

        switch ($field) {
            case 'nombres':
            case 'apellidos':
                $label = $field === 'nombres' ? 'sus nombres' : 'sus apellidos';
                if (mb_strlen($normalized) < 2 || ! VoiceText::isLettersOnly($normalized)) {
                    return ['value' => null, 'error' => 'Solo necesito letras. Diga '.$label.' otra vez.'];
                }
                if (mb_strlen($normalized) > 80) {
                    return ['value' => null, 'error' => 'Es demasiado largo. Diga '.$label.' otra vez.'];
                }
                if ($field === 'apellidos') {
                    $username = self::usernameFor($data['nombres'] ?? '', $normalized);
                    if (User::where('username', $username)->exists()) {
                        return [
                            'value' => $normalized,
                            'error' => null,
                            'restart_at' => 'nombres',
                            'note' => 'Ya existe una cuenta con el nombre '.$username.'. '
                                .'Si es suya, vaya a la pantalla de login. Si no, diga sus nombres completos, '
                                .'incluyendo su segundo nombre.',
                        ];
                    }
                }

                return ['value' => $normalized, 'error' => null];

            case 'sobre_mi':
                if (mb_strlen($normalized) < 3) {
                    return ['value' => null, 'error' => 'No le entendí. Cuénteme brevemente sobre usted.'];
                }

                return ['value' => mb_substr($normalized, 0, 500), 'error' => null];

            case 'ubicacion':
                if (mb_strlen($normalized) < 2) {
                    return ['value' => null, 'error' => 'No le entendí.'];
                }

                return ['value' => mb_substr($normalized, 0, 150), 'error' => null];

            case 'telefono_whatsapp':
                $phone = SpokenDigits::parsePhone($transcript);
                if (! $phone) {
                    return ['value' => null, 'error' => 'No entendí el número.'];
                }

                return ['value' => $phone, 'error' => null];

            case 'pin':
                $pin = SpokenDigits::parseFourDigits($transcript);
                if (! $pin) {
                    return ['value' => null, 'error' => 'El PIN debe tener exactamente cuatro dígitos.'];
                }

                return ['value' => $pin, 'error' => null];
        }

        return ['value' => $normalized, 'error' => null];
    }

    protected function question(array $session, string $note = ''): array
    {
        $fields = array_keys(self::STEPS);
        $prompt = self::STEPS[$session['step']];

        return [
            'type' => 'question',
            'field' => $session['step'],
            'index' => array_search($session['step'], $fields, true) + 1,
            'total' => count($fields),
            'prompt' => $prompt,
            'speak' => $note.$prompt,
        ];
    }

    protected function nextStep(string $field): ?string
    {
        $fields = array_keys(self::STEPS);
        $index = array_search($field, $fields, true);

        return $fields[$index + 1] ?? null;
    }

    protected function previousStep(string $field): ?string
    {
        $fields = array_keys(self::STEPS);
        $index = array_search($field, $fields, true);

        return $index > 0 ? $fields[$index - 1] : null;
    }

    protected function load(): ?array
    {
        return cache()->get($this->key());
    }

    protected function save(array $session): void
    {
        cache()->put($this->key(), $session, now()->addDay());
    }

    // La clave se calcula al usarla (no en el constructor) para que
    // corresponda a la sesión ya iniciada por el middleware.
    protected function key(): string
    {
        return $this->sessionKey ?? 'voice_registration.'.session()->getId();
    }
}
