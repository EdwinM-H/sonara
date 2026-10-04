<?php

namespace App\Services\Verification;

use App\Models\User;

/**
 * Revisa que el registro de un emprendedor tenga completos los seis
 * campos del registro por voz. Nunca expone el PIN: solo informa si
 * existe y si está guardado como hash.
 */
class EntrepreneurRecordValidator
{
    /** @var array<string, string> campo => etiqueta */
    public const FIELDS = [
        'nombres' => 'Nombres',
        'apellidos' => 'Apellidos',
        'sobre_mi' => 'Sobre mí',
        'ubicacion' => 'Ubicación',
        'telefono_whatsapp' => 'Teléfono WhatsApp',
        'pin' => 'PIN',
    ];

    /** @return array<string, string> campo => problema (vacío si el registro está completo) */
    public function issuesFor(User $user): array
    {
        $profile = $user->entrepreneurProfile;
        $values = [
            'nombres' => $user->first_name,
            'apellidos' => $user->last_name,
            'sobre_mi' => $profile?->personal_description,
            'ubicacion' => $profile?->location,
            'telefono_whatsapp' => $user->phone,
        ];

        $issues = [];
        foreach ($values as $field => $value) {
            if (trim((string) $value) === '') {
                $issues[$field] = 'vacío';
            }
        }

        // getRawOriginal evita el cast "hashed" y lee el valor tal cual
        // está en la base de datos, solo para comprobar su formato.
        $pin = (string) $user->getRawOriginal('voice_pin');
        if ($pin === '') {
            $issues['pin'] = 'vacío';
        } elseif (! self::isHash($pin)) {
            $issues['pin'] = 'no está hasheado';
        }

        return $issues;
    }

    public function pinStatus(User $user): string
    {
        $pin = (string) $user->getRawOriginal('voice_pin');

        return $pin === '' ? 'falta' : (self::isHash($pin) ? 'hasheado' : 'texto plano');
    }

    public static function isHash(string $value): bool
    {
        return password_get_info($value)['algo'] !== null;
    }
}
