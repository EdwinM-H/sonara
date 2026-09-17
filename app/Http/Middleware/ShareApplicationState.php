<?php

namespace App\Http\Middleware;

use App\Services\Voice\VoiceManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareApplicationState
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Preferencias de accesibilidad del usuario autenticado
        $accessibility = $user?->getsAccessibilityPreference();

        View::share('accessibilityPref', $accessibility);
        View::share('voiceConfig', app(VoiceManager::class)->clientConfig());

        return $next($request);
    }
}