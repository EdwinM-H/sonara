<?php

namespace App\Services\Assistant;

/**
 * Menú por voz del dashboard de emprendedor: interpreta la opción
 * dictada y devuelve a dónde navegar.
 */
class EntrepreneurVoiceMenu
{
    public const PROMPT = 'Te encuentras en el dashboard de emprendedor. ¿Qué deseas hacer ahora? '
        .'Registrar un nuevo emprendimiento, ver mis emprendimientos, o ver mis solicitudes.';

    public const OPTIONS = 'Puedes decir: registrar un nuevo emprendimiento, ver mis emprendimientos, o ver mis solicitudes.';

    /** @return array{type: string, speak: string, redirect?: string} */
    public function interpret(?string $transcript): array
    {
        $text = VoiceText::normalize($transcript);
        $words = explode(' ', $text);
        $has = fn (string ...$needles) => (bool) array_intersect($needles, $words);

        if ($text === 'repetir' || $text === 'ayuda') {
            return ['type' => 'question', 'speak' => self::PROMPT];
        }

        // "registrar un nuevo emprendimiento" también contiene
        // "emprendimiento": se revisa antes que "ver mis emprendimientos".
        if ($has('registrar', 'registro', 'nuevo', 'nueva', 'crear', 'agregar', 'uno', '1', 'primera', 'primero')) {
            return $this->go('Muy bien, vamos a registrar un nuevo emprendimiento.', 'entrepreneur.businesses.voice');
        }

        if ($has('solicitud', 'solicitudes', 'pedido', 'pedidos', 'tres', '3', 'tercera', 'tercero')) {
            return $this->go('Muy bien, vamos a tus solicitudes.', 'entrepreneur.requests.index');
        }

        if ($has('emprendimiento', 'emprendimientos', 'negocio', 'negocios', 'dos', '2', 'segunda', 'segundo')) {
            return $this->go('Muy bien, vamos a tus emprendimientos.', 'entrepreneur.businesses.index');
        }

        return ['type' => 'question', 'speak' => 'No te entendí. '.self::OPTIONS];
    }

    protected function go(string $speak, string $route): array
    {
        return ['type' => 'navigate', 'speak' => $speak, 'redirect' => route($route)];
    }
}
