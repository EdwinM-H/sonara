<?php

namespace App\Services\Assistant;

/**
 * Normalización única de todo texto dictado por voz: minúsculas, sin
 * tildes (ni ñ/ü) y sin caracteres especiales. Se aplica antes de
 * procesar, comparar o guardar cualquier respuesta, para que el flujo
 * no dependa de cómo el reconocimiento de voz escriba mayúsculas,
 * acentos o signos de puntuación.
 */
class VoiceText
{
    public static function normalize(?string $text): string
    {
        $text = mb_strtolower(trim((string) $text));
        $text = strtr($text, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ]);
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    /** Solo letras y espacios (nombres y apellidos). */
    public static function isLettersOnly(string $normalized): bool
    {
        return (bool) preg_match('/^[a-z]+( [a-z]+)*$/', $normalized);
    }
}
