<?php

namespace App\Services\Assistant;

/**
 * Interpreta números dictados por voz (dígitos sueltos o palabras en
 * español) para códigos como el PIN o el número de WhatsApp.
 */
class SpokenDigits
{
    /** @var array<string, string> */
    protected const WORDS = [
        'cero' => '0',
        'uno' => '1',
        'un' => '1',
        'una' => '1',
        'dos' => '2',
        'tres' => '3',
        'cuatro' => '4',
        'cinco' => '5',
        'seis' => '6',
        'siete' => '7',
        'ocho' => '8',
        'nueve' => '9',
    ];

    /**
     * Extrae un PIN de exactamente 4 dígitos de un texto dictado.
     * Acepta dígitos literales ("7205"), palabras ("siete dos cero cinco")
     * o una mezcla de ambos. Devuelve null si no hay exactamente 4 dígitos.
     */
    public static function parseFourDigits(string $text): ?string
    {
        $digits = self::extract($text);

        return strlen($digits) === 4 ? $digits : null;
    }

    /**
     * Extrae un número de teléfono (solo dígitos) de un texto dictado.
     * Devuelve null si la cantidad de dígitos está fuera del rango.
     */
    public static function parsePhone(string $text, int $min = 6, int $max = 15): ?string
    {
        $digits = self::extract($text);
        $length = strlen($digits);

        return $length >= $min && $length <= $max ? $digits : null;
    }

    /**
     * Formatea dígitos para que la síntesis de voz los pronuncie uno por
     * uno (evita que "7205" se lea como "siete mil doscientos cinco").
     */
    public static function spaceOut(string $digits): string
    {
        return implode(' ', str_split($digits));
    }

    /**
     * Concatena, en orden, los dígitos literales y las palabras numéricas
     * del texto; ignora cualquier otra palabra ("mi número es ...").
     */
    protected static function extract(string $text): string
    {
        $tokens = explode(' ', VoiceText::normalize($text));
        $digits = '';
        foreach ($tokens as $token) {
            if ($token !== '' && ctype_digit($token)) {
                $digits .= $token;
            } elseif (isset(self::WORDS[$token])) {
                $digits .= self::WORDS[$token];
            }
        }

        return $digits;
    }
}
