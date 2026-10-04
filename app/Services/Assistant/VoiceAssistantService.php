<?php

namespace App\Services\Assistant;

use App\Models\User;

/**
 * Registro de emprendedor por voz.
 *
 * Pregunta los seis campos uno por uno (nombres, apellidos, sobre mí,
 * ubicación, WhatsApp y PIN). Cada respuesta se normaliza (minúsculas,
 * sin tildes ni caracteres especiales) y se valida antes de pasar al
 * siguiente campo. La creación de la cuenta la hace el controlador
 * cuando process() devuelve el tipo "complete".
 */
class VoiceAssistantService extends VoiceFlow
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

    protected function steps(): array
    {
        return self::STEPS;
    }

    protected function intro(): string
    {
        return self::INTRO;
    }

    protected function help(): string
    {
        return self::HELP;
    }

    protected function exitMessage(): string
    {
        return 'El registro fue cancelado. Puede empezar de nuevo cuando quiera.';
    }

    protected function cachePrefix(): string
    {
        return 'voice_registration';
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
}
