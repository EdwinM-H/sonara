@php
    $voiceUser = auth()->user();
    $voicePanelRoute = null;
    $voiceNotificationsRoute = null;
    if ($voiceUser) {
        $voicePanelRoute = match (true) {
            $voiceUser->isAdmin() => route('admin.dashboard'),
            $voiceUser->isEntrepreneur() => route('entrepreneur.dashboard'),
            $voiceUser->isCustomer() => route('customer.dashboard'),
            default => null,
        };
        $voiceNotificationsRoute = match (true) {
            $voiceUser->isAdmin() => route('admin.notifications.index'),
            $voiceUser->isEntrepreneur() => route('entrepreneur.notifications.index'),
            default => null,
        };
    }
@endphp

<div x-data="siteVoiceAssistant"
     data-has-user="{{ $voiceUser ? '1' : '0' }}"
     data-nav-home="{{ route('public.home') }}"
     data-nav-explore="{{ route('public.explore') }}"
     data-nav-categories="{{ route('public.categories') }}"
     data-nav-register="{{ route('register') }}"
     data-nav-login="{{ route('login') }}"
     data-nav-voice="{{ route('voice-registration.index') }}"
     data-nav-profile="{{ $voiceUser ? route('profile.edit') : '' }}"
     data-nav-notifications="{{ $voiceNotificationsRoute ?? '' }}"
     data-nav-panel="{{ $voicePanelRoute ?? '' }}"
     aria-label="Asistente por voz de Sonara"
     class="fixed bottom-20 right-4 z-[60] lg:bottom-4">

    {{-- Botón flotante --}}
    <button type="button"
            @click="toggle()"
            aria-label="Asistente por voz (Alt + V)"
            class="voice-fab"
            :aria-expanded="open.toString()">
        <span aria-hidden="true" x-show="!listening"><x-icon name="mic" class="w-7 h-7" /></span>
        <span aria-hidden="true" x-show="listening" x-cloak class="voice-fab-listening" aria-label="Escuchando"></span>
        <span class="sr-only">Asistente por voz</span>
    </button>

    {{-- Panel --}}
    <div x-show="open" x-cloak role="dialog" aria-label="Panel del asistente por voz"
         class="absolute bottom-20 right-0 w-80 max-w-[90vw] rounded-2xl border border-purple-200 bg-white p-4 shadow-2xl space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-purple-900 flex items-center gap-2">
                <span class="grid place-items-center w-8 h-8 rounded-lg bg-purple-50 text-purple-700" aria-hidden="true">
                    <x-icon name="mic" class="w-4 h-4" />
                </span>
                Asistente por voz
            </h2>
            <button type="button" @click="close()" class="icon-btn text-gray-500" aria-label="Cerrar asistente"><x-icon name="close" /></button>
        </div>

        <p role="status" aria-live="polite" class="rounded-xl bg-purple-50 px-3 py-2 text-sm text-purple-900 min-h-[2.5rem]" x-text="status">
            Escuchando…
        </p>

        <div class="flex flex-wrap gap-2">
            <button type="button" @click="startListen(12000)" x-show="!listening" class="btn btn-sm btn-primary">
                <x-icon name="mic" class="w-4 h-4" /> Escuchar
            </button>
            <button type="button" @click="close()" x-show="listening" class="btn btn-sm btn-danger">
                Detener
            </button>
            <button type="button" @click="readPage()" class="btn btn-sm btn-secondary">
                <x-icon name="document" class="w-4 h-4" /> Leer página
            </button>
            <button type="button" @click="say('Puedes decir: ' + commandList() + '. O leer página.')" class="btn btn-sm btn-secondary">
                <x-icon name="info" class="w-4 h-4" /> Ayuda
            </button>
        </div>

        <p class="text-xs text-gray-500">
            Di una de estas opciones en voz alta: inicio, explorar, categorías, registrarme,
            iniciar sesión, registro por voz, mi panel, notificaciones o leer página.
            También puedes presionar <kbd class="px-1.5 py-0.5 rounded bg-gray-100 border border-gray-300">Alt</kbd>+<kbd class="px-1.5 py-0.5 rounded bg-gray-100 border border-gray-300">V</kbd>.
        </p>
    </div>
</div>