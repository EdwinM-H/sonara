<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'SONARA — Emprendimientos accesibles') · {{ config('app.name', 'SONARA') }}</title>
        <meta name="description" content="SONARA: plataforma accesible para emprendedores con discapacidad visual. Explora y solicita productos y servicios.">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased pb-16 lg:pb-0 {{ $bodyClasses ?? '' }}"
          data-speech-rate="{{ $accessibilityPref->speech_rate ?? 'normal' }}"
          data-speech-volume="{{ $accessibilityPref->volume ?? 'normal' }}"
          data-auto-read="{{ auth()->check() ? (($accessibilityPref->auto_read ?? false) ? '1' : '0') : '1' }}"
          data-repeat-prompts="{{ ($accessibilityPref->repeat_prompts ?? true) ? '1' : '0' }}">
        <a class="skip-link" href="#main-content">Saltar al contenido principal</a>

        @include('partials.flash')

        @include('layouts.navigation')

        @yield('hero')

        <main id="main-content" class="min-h-[70vh]">
            @yield('content')
        </main>

        @include('layouts.footer')

        @include('partials.mobile-nav')

        @include('partials.voice-assistant')

        @stack('scripts')
    </body>
</html>