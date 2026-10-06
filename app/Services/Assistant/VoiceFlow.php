<?php

namespace App\Services\Assistant;

/**
 * Base de los formularios guiados por voz: hace una pregunta por campo,
 * normaliza y valida cada respuesta antes de pasar al siguiente, y
 * entiende los comandos repetir, atrás, ayuda y cancelar. El progreso
 * vive en caché por sesión para poder retomarlo.
 *
 * Cuando se responde el último campo, process() devuelve el tipo
 * "complete" con los datos; esa última respuesta NO se guarda en caché
 * (en el registro de emprendedor es el PIN), y quien llama decide qué
 * hacer con ellos.
 */
abstract class VoiceFlow
{
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

    /** @return array<string, string> campo => pregunta */
    abstract protected function steps(): array;

    /**
     * @return array{value: mixed, error: ?string, speak_only?: bool, free?: bool, restart_at?: string, note?: string}
     *   speak_only: el error ya incluye lo que hay que decir (no se repite la pregunta).
     *   free: el valor se aceptó fuera del catálogo (valor libre).
     */
    abstract protected function validate(string $field, string $transcript, array $data): array;

    abstract protected function cachePrefix(): string;

    /** Si el campo no aplica con las respuestas dadas (pregunta condicional). */
    protected function skips(string $field, array $data): bool
    {
        return false;
    }

    protected function intro(): string
    {
        return '';
    }

    protected function help(): string
    {
        return 'Responda cada pregunta después del pitido. Puede decir: repetir, atrás o cancelar. ';
    }

    protected function exitMessage(): string
    {
        return 'Se canceló. Puede empezar de nuevo cuando quiera.';
    }

    public function start(): array
    {
        $session = ['step' => array_key_first($this->steps()), 'data' => []];
        $this->save($session);

        return $this->question($session, $this->intro());
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

        switch (static::COMMANDS[$normalized] ?? null) {
            case 'repeat':
                return $this->question($session);
            case 'help':
                return $this->question($session, $this->help());
            case 'exit':
                $this->resetSession();

                return ['type' => 'exited', 'speak' => $this->exitMessage()];
            case 'back':
                $previous = $this->previousStep($session['step'], $session['data']);
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
            return ($result['speak_only'] ?? false)
                ? $this->question($session, $result['error'], replacePrompt: true)
                : $this->question($session, $result['error'].' ');
        }

        $session['data'][$field] = $result['value'];
        $libres = array_values(array_diff($session['data']['_libres'] ?? [], [$field]));
        if ($result['free'] ?? false) {
            $libres[] = $field;
        }
        if ($libres) {
            $session['data']['_libres'] = $libres;
        } else {
            unset($session['data']['_libres']);
        }

        if ($result['restart_at'] ?? null) {
            $session['step'] = $result['restart_at'];
            $this->save($session);

            return $this->question($session, $result['note'].' ');
        }

        $next = $this->nextStep($field, $session['data']);
        if ($next === null) {
            // No se guarda: la última respuesta solo viaja en la respuesta.
            return ['type' => 'complete', 'data' => $session['data']];
        }

        $session['step'] = $next;
        $this->save($session);

        return $this->question($session);
    }

    protected function question(array $session, string $note = '', bool $replacePrompt = false): array
    {
        $steps = $this->steps();
        $fields = $this->activeFields($session['data']);
        $prompt = $steps[$session['step']];

        return [
            'type' => 'question',
            'field' => $session['step'],
            'index' => array_search($session['step'], $fields, true) + 1,
            'total' => count($fields),
            'prompt' => $replacePrompt ? $note : $prompt,
            'speak' => $replacePrompt ? $note : $note.$prompt,
        ];
    }

    /** Campos que se preguntan con las respuestas dadas hasta ahora. */
    protected function activeFields(array $data): array
    {
        return array_values(array_filter(
            array_keys($this->steps()),
            fn (string $field) => ! $this->skips($field, $data),
        ));
    }

    protected function nextStep(string $field, array $data = []): ?string
    {
        $fields = array_keys($this->steps());
        for ($i = array_search($field, $fields, true) + 1; $i < count($fields); $i++) {
            if (! $this->skips($fields[$i], $data)) {
                return $fields[$i];
            }
        }

        return null;
    }

    protected function previousStep(string $field, array $data = []): ?string
    {
        $fields = array_keys($this->steps());
        for ($i = array_search($field, $fields, true) - 1; $i >= 0; $i--) {
            if (! $this->skips($fields[$i], $data)) {
                return $fields[$i];
            }
        }

        return null;
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
        return $this->sessionKey ?? $this->cachePrefix().'.'.session()->getId();
    }
}
