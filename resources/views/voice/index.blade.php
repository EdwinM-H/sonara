@extends('layouts.app')

@php $isLogin = $mode === 'login'; @endphp

@section('title', $isLogin ? 'Login por voz' : 'Registro por voz')

@section('content')
<div class="w-full" style="background-color: var(--color-ink)">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14"
         x-data="voiceAssistant" data-standalone-voice
         data-mode="{{ $mode }}"
         data-has-session="{{ $hasSession ? '1' : '0' }}"
         @if ($isLogin)
             data-start-route="{{ route('voice-login.start') }}"
             data-process-route="{{ route('voice-login.process') }}"
         @else
             data-start-route="{{ route('voice-registration.start') }}"
             data-resume-route="{{ route('voice-registration.resume') }}"
             data-process-route="{{ route('voice-registration.process') }}"
         @endif>

        <header class="text-center mb-10">
            <p class="mono-label !text-purple-300">{{ $isLogin ? 'Login de emprendedor' : 'Registro de emprendedor' }}</p>
            <h1 class="mt-2 text-3xl sm:text-4xl font-display font-extrabold text-white text-balance">
                {{ $isLogin ? 'Ingrese con su voz.' : 'Cree su cuenta hablando.' }}
            </h1>
            <p class="text-purple-200 mt-2 text-balance">
                {{ $isLogin
                    ? 'Su usuario es su nombre completo y su contraseña es su PIN de 4 dígitos.'
                    : 'Responda cada pregunta después del pitido.' }}
            </p>
        </header>

        <div class="rounded-[2rem] bg-white/[0.04] border border-white/10 p-6 sm:p-10">
            @unless ($isLogin)
                <div x-show="total" x-cloak class="mb-8">
                    <p class="mono-label !text-purple-300 mb-2">Pregunta <span x-text="index"></span> de <span x-text="total"></span></p>
                    <div class="h-1.5 rounded-full bg-white/10 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500" style="background: var(--color-accent)"
                             :style="`width: ${(index / total) * 100}%`"></div>
                    </div>
                </div>
            @endunless

            {{-- Orbe --}}
            <div class="flex justify-center mb-8">
                <div class="relative w-28 h-28 grid place-items-center">
                    <template x-if="listening"><div class="voice-orb-ring"></div></template>
                    <template x-if="listening"><div class="voice-orb-ring delay-1"></div></template>
                    <template x-if="listening"><div class="voice-orb-ring delay-2"></div></template>
                    <div class="relative grid place-items-center w-20 h-20 rounded-full transition-colors"
                         :style="listening ? 'background: var(--color-accent)' : 'background: var(--color-primary)'">
                        <x-icon name="mic" class="w-8 h-8" x-bind:class="listening ? 'text-[var(--color-ink)]' : 'text-white'" />
                    </div>
                </div>
            </div>

            <div role="status" aria-live="polite" class="text-center min-h-[80px]">
                <p class="text-2xl sm:text-3xl font-display font-bold text-white text-balance leading-tight"
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

            {{-- Alternativa por teclado (el flujo principal es solo por voz) --}}
            <form @submit.prevent="sendTypedAnswer()" class="mt-8 pt-8 border-t border-white/10 grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3">
                <label for="typed" class="sr-only">Respuesta por teclado</label>
                <input id="typed" x-model="typedAnswer" type="text" autocomplete="off"
                       placeholder="¿Sin micrófono? Escriba su respuesta aquí"
                       class="input-text !bg-white/10 !border-white/20 !text-white placeholder:!text-purple-300" :disabled="!running">
                <button type="submit" class="btn btn-dark" :disabled="!running">Enviar</button>
            </form>

            <p class="mt-6 text-sm text-purple-200 text-center">
                Comandos: <strong class="text-white">repetir</strong>, <strong class="text-white">atrás</strong>, <strong class="text-white">ayuda</strong>@unless ($isLogin), <strong class="text-white">cancelar</strong>@endunless.
            </p>
        </div>

        <p class="text-center text-sm text-purple-300 mt-8">
            @if ($isLogin)
                ¿No tiene cuenta? <a href="{{ route('voice-registration.index') }}" class="text-white font-semibold underline">Registro por voz</a>
            @else
                ¿Ya tiene cuenta? <a href="{{ route('voice-login.index') }}" class="text-white font-semibold underline">Login por voz</a>
            @endif
        </p>
    </div>
</div>
@endsection
