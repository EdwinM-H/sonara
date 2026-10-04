@extends('layouts.panel-entrepreneur')

@section('panel-content')
    <x-panel-header title="Registrar emprendimiento por voz" subtitle="Responde cada pregunta después del pitido. Al terminar, crearemos la imagen y publicaremos tu anuncio." />

    <section x-data="voiceAssistant" data-standalone-voice
             data-mode="business"
             data-has-session="{{ $hasSession ? '1' : '0' }}"
             data-start-route="{{ route('entrepreneur.businesses.voice.start') }}"
             data-resume-route="{{ route('entrepreneur.businesses.voice.resume') }}"
             data-process-route="{{ route('entrepreneur.businesses.voice.process') }}"
             aria-label="Asistente de voz"
             class="rounded-[2rem] p-6 sm:p-10 text-white max-w-3xl"
             style="background-color: var(--color-ink)">

        <div x-show="total" x-cloak class="mb-8">
            <p class="mono-label !text-purple-300 mb-2">Pregunta <span x-text="index"></span> de <span x-text="total"></span></p>
            <div class="h-1.5 rounded-full bg-white/10 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500" style="background: var(--color-accent)"
                     :style="`width: ${(index / total) * 100}%`"></div>
            </div>
        </div>

        <div class="flex justify-center mb-8">
            <div class="relative w-28 h-28 grid place-items-center">
                <template x-if="listening"><div class="voice-orb-ring"></div></template>
                <template x-if="listening"><div class="voice-orb-ring delay-1"></div></template>
                <div class="relative grid place-items-center w-20 h-20 rounded-full transition-colors"
                     :style="listening ? 'background: var(--color-accent)' : 'background: var(--color-primary)'">
                    <x-icon name="mic" class="w-8 h-8" x-bind:class="listening ? 'text-[var(--color-ink)]' : 'text-white'" />
                </div>
            </div>
        </div>

        <div role="status" aria-live="polite" class="text-center min-h-[80px]">
            <p class="text-xl sm:text-2xl font-display font-bold text-balance leading-tight"
               x-text="program || 'Presione cualquier tecla para activar el asistente de voz.'"></p>
            <p class="text-purple-200 mt-3 font-mono text-sm" x-show="message" x-text="message"></p>
        </div>

        <div class="flex flex-wrap gap-3 justify-center mt-8">
            <button type="button" @click="restart()" x-show="!running" class="btn btn-dark text-lg">
                <x-icon name="play" class="w-5 h-5" /> Comenzar
            </button>
            <button type="button" @click="stopSpeak(); startListening()" x-show="running && !listening" x-cloak class="btn btn-dark">
                <x-icon name="mic" class="w-5 h-5" /> Responder por voz
            </button>
            <button type="button" @click="repeat()" x-show="running" x-cloak class="btn btn-secondary !bg-transparent !text-white !border-white/30 hover:!bg-white/10">
                <x-icon name="refresh" class="w-5 h-5" /> Repetir
            </button>
            <button type="button" @click="goBack()" x-show="running" x-cloak class="btn btn-secondary !bg-transparent !text-white !border-white/30 hover:!bg-white/10">
                <x-icon name="chevron-right" class="w-5 h-5 rotate-180" /> Atrás
            </button>
        </div>

        {{-- Alternativa por teclado --}}
        <form @submit.prevent="sendTypedAnswer()" class="mt-8 pt-8 border-t border-white/10 grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3">
            <label for="typed" class="sr-only">Respuesta por teclado</label>
            <input id="typed" x-model="typedAnswer" type="text" autocomplete="off"
                   placeholder="¿Sin micrófono? Escribe tu respuesta aquí"
                   class="input-text !bg-white/10 !border-white/20 !text-white placeholder:!text-purple-300" :disabled="!running">
            <button type="submit" class="btn btn-dark" :disabled="!running">Enviar</button>
        </form>

        <p class="mt-6 text-sm text-purple-200 text-center">
            Comandos: <strong class="text-white">repetir</strong>, <strong class="text-white">atrás</strong>,
            <strong class="text-white">ayuda</strong>, <strong class="text-white">cancelar</strong>.
        </p>
    </section>

    <p class="mt-6 text-sm text-gray-600">
        ¿Prefieres un formulario? <a href="{{ route('entrepreneur.businesses.create') }}" class="underline font-semibold">Registrar con formulario</a>.
    </p>
@endsection
