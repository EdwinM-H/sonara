<?php

namespace App\Services\Assistant;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Verifica las credenciales del login por voz (usuario = nombre completo
 * normalizado, PIN de 4 dígitos contra su hash) y aplica un límite de
 * intentos, ya que un PIN de 4 dígitos tiene poca entropía frente a
 * fuerza bruta.
 */
class VoiceLoginAuthenticator
{
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 300;

    /** @return array{type: string, speak: string, redirect?: string} */
    public function attempt(Request $request, string $usuario, string $pin): array
    {
        $usuario = VoiceText::normalize($usuario);
        $throttleKey = 'voice-login|'.$usuario.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($throttleKey) / 60);

            return [
                'type' => 'locked',
                'speak' => 'Demasiados intentos fallidos. Por seguridad, espere '.$minutes.' minutos y vuelva a intentarlo.',
            ];
        }

        $user = User::where('username', $usuario)->first();
        $valid = $user
            && $user->hasVoicePin()
            && Hash::check($pin, $user->voice_pin)
            && $user->isEntrepreneur()
            && $user->isActive();

        if (! $valid) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            return [
                'type' => 'login_failed',
                // No se dice cuál de los dos datos falló, para no revelar
                // qué nombres tienen cuenta.
                'speak' => 'El usuario o el PIN no son correctos. Intentemos de nuevo. ',
            ];
        }

        RateLimiter::clear($throttleKey);

        Auth::login($user);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return [
            'type' => 'success',
            'speak' => 'Perfecto, bienvenido '.$user->name.'.',
            'redirect' => route('entrepreneur.dashboard'),
        ];
    }
}
