<footer class="text-slate-300 mt-16" style="background-color: var(--color-ink)">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-14">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
            <div>
                <p class="flex items-center gap-2.5">
                    <span class="logo-mark text-sm" aria-hidden="true">S</span>
                    <span class="text-xl font-display font-black tracking-tight text-white">SONARA</span>
                </p>
                <p class="mt-3 text-sm leading-relaxed">
                    Plataforma accesible para emprendedores con discapacidad visual.
                    Promovemos la comercialización de productos y servicios inclusivos.
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="badge badge-neutral !bg-slate-800 !text-slate-200">Inclusivo</span>
                    <span class="badge badge-neutral !bg-slate-800 !text-slate-200">Voces guías</span>
                    <span class="badge badge-neutral !bg-slate-800 !text-slate-200">WCAG 2.2</span>
                </div>
            </div>

            <nav aria-label="Explora">
                <p class="font-bold text-white mb-3">Explora</p>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('public.home') }}" class="hover:text-white inline-flex items-center gap-1.5">Inicio</a></li>
                    <li><a href="{{ route('public.explore') }}" class="hover:text-white inline-flex items-center gap-1.5">Explorar emprendimientos</a></li>
                    <li><a href="{{ route('public.categories') }}" class="hover:text-white inline-flex items-center gap-1.5">Categorías</a></li>
                </ul>
            </nav>

            <nav aria-label="Emprendedores">
                <p class="font-bold text-white mb-3">Emprendedores</p>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('register') }}" class="hover:text-white inline-flex items-center gap-1.5">Crear cuenta</a></li>
                    <li><a href="{{ route('voice-registration.index') }}" class="hover:text-white inline-flex items-center gap-1.5">Registro asistido por voz</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-white inline-flex items-center gap-1.5">Iniciar sesión</a></li>
                </ul>
            </nav>

            <div>
                <p class="font-bold text-white mb-3">Accesibilidad</p>
                <p class="text-sm leading-relaxed">
                    Diseñado para navegar con voz, teclado y lectores de pantalla.
                    Botones grandes, alto contraste y fuentes legibles.
                </p>
            </div>
        </div>

        <p class="mt-10 border-t border-slate-800 pt-6 text-center text-xs text-slate-500">
            © {{ date('Y') }} SONARA — Accesibilidad WCAG 2.2 · Hecho con accesibilidad en mente desde el diseño.
        </p>
    </div>
</footer>