<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica las preferencias de accesibilidad del usuario a la vista:
 * agrega clases CSS (alto contraste, tamaño de letra) y comparte la
 * tabla de preferencias con las plantillas.
 */
class ApplyUserPreferences
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $prefs = null;
        $bodyClasses = [];

        if ($user) {
            $prefs = $user->getsAccessibilityPreference();
            View::share('accessibilityPref', $prefs);

            if ($prefs->high_contrast) {
                $bodyClasses[] = 'high-contrast';
            }
            if ($prefs->font_size && $prefs->font_size !== 'normal') {
                $bodyClasses[] = 'font-'.$prefs->font_size;
            }
        }

        View::share('bodyClasses', implode(' ', $bodyClasses));

        $response = $next($request);

        return $response;
    }
}