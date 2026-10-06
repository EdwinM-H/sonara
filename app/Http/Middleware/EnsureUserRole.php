<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        // Navegación normal (no AJAX): el admin vuelve a su panel y el resto
        // al login, en lugar de una página de error.
        if (! $request->expectsJson() && $request->isMethod('GET')) {
            return $user->isAdmin()
                ? redirect()->route('admin.dashboard')
                : redirect()->route('login');
        }

        abort(403, 'No tienes permiso para acceder a esta sección.');
    }
}
