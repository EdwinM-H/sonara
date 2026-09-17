<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'SONARA') · {{ config('app.name', 'SONARA') }}</title>
        <meta name="description" content="SONARA: plataforma accesible para emprendedores con discapacidad visual. Explora y solicita productos y servicios.">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased {{ $bodyClasses ?? '' }}">
        <a class="skip-link" href="#main-content">Saltar al contenido principal</a>

        @include('partials.flash')

        <div class="min-h-screen lg:grid lg:grid-cols-2">
            {{-- Panel de marca (escritorio) --}}
            <div class="relative hidden lg:flex flex-col justify-between text-white p-12 overflow-hidden" style="background-color: var(--color-primary-deep)">
                <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full opacity-30 blur-3xl" style="background: var(--color-primary)" aria-hidden="true"></div>
                <div class="relative z-10">
                    <a href="{{ route('public.home') }}" class="inline-flex items-center gap-3">
                        <span class="logo-mark text-sm" aria-hidden="true">S</span>
                        <span class="text-3xl font-display font-black tracking-tight">SONARA</span>
                    </a>
                    <div class="mt-16 max-w-md">
                        <p class="mono-label !text-purple-200">Plataforma inclusiva</p>
                        <h1 class="mt-3 text-5xl font-display font-black leading-[0.95] text-balance">Emprender es para todos los que tienen una idea.</h1>
                        <p class="mt-5 text-purple-100 text-lg leading-relaxed">
                            Explora, publica y solicita productos y servicios en una plataforma
                            diseñada con accesibilidad desde el primer píxel.
                        </p>
                        <div class="mt-8 flex flex-wrap gap-2.5">
                            <span class="badge !bg-white/10 !text-white border border-white/20">🎙️ Voz</span>
                            <span class="badge !bg-white/10 !text-white border border-white/20">⌨️ Teclado</span>
                            <span class="badge bg-[var(--color-accent)] !text-[var(--color-ink)] border-0">◔ Alto contraste</span>
                        </div>
                    </div>
                </div>
                <p class="relative z-10 mono-label !text-purple-300">© {{ date('Y') }} SONARA — Accesibilidad WCAG 2.2</p>
            </div>

            {{-- Formulario --}}
            <main id="main-content" class="flex items-center justify-center px-4 sm:px-6 py-10 lg:py-0" style="background-color: var(--color-cream)">
                <div class="w-full max-w-md u-fade-up">
                    <a href="{{ route('public.home') }}" class="lg:hidden inline-flex items-center gap-2.5 mb-8">
                        <span class="logo-mark text-sm" aria-hidden="true">S</span>
                        <span class="text-2xl font-display font-black tracking-tight text-gray-900">SONARA</span>
                    </a>
                    <div class="bg-white rounded-2xl border-[1.5px] border-gray-200 shadow-lift p-6 sm:p-8">
                        @yield('content', $slot ?? '')
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>