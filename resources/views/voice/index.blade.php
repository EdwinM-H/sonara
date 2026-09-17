@extends('layouts.app')

@section('title', 'Registro guiado por voz')

@section('content')
<div class="w-full" style="background-color: var(--color-ink)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14"
         x-data="voiceAssistant" data-standalone-voice
         data-start-route="{{ route('voice-registration.start') }}"
         data-resume-route="{{ route('voice-registration.resume') }}"
         data-process-route="{{ route('voice-registration.process') }}"
         data-review-route="{{ route('voice-registration.review') }}"
         data-confirm-route="{{ route('voice-registration.confirm') }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <header class="text-center lg:text-left mb-10">
            <p class="mono-label !text-purple-300">Registro autónomo por voz</p>
            <h1 class="mt-2 text-3xl sm:text-4xl font-display font-extrabold text-white text-balance">Te guiamos paso a paso, hablando.</h1>
            <p class="text-purple-200 mt-2 max-w-xl mx-auto lg:mx-0 text-balance">
                Responde con tu voz o el teclado. Puedes retomar el proceso en cualquier momento.
            </p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-8 items-start">
            {{-- Progreso --}}
            <aside class="lg:sticky lg:top-24" aria-label="Progreso del registro"
                   x-data="{
                        steps: [
                            ['welcome','Bienvenida'],['first_name','Nombre'],['last_name','Apellidos'],
                            ['personal_description','Sobre ti'],['business_name','Tu emprendimiento'],
                            ['business_description','Qué ofreces'],['category','Categoría'],['type','Tipo'],
                            ['price','Precio'],['offerings','Productos/servicios'],['region','Región'],
                            ['province','Provincia'],['district','Distrito'],['schedule','Horario'],
                            ['phone','Teléfono'],['whatsapp','WhatsApp'],['email','Correo'],
                            ['contact_extra','Contacto extra'],['username','Usuario de acceso'],['confirmation','Confirmación'],
                        ],
                        get index() { return Math.max(0, this.steps.findIndex(s => s[0] === state)); },
                        get total() { return this.steps.length; },
                   }">
                <p class="mono-label !text-purple-300 mb-2" role="status" aria-live="polite">
                    <span x-show="phase !== 'review'">Paso <span x-text="index + 1"></span> de <span x-text="total"></span></span>
                    <span x-show="phase === 'review'" x-cloak>Revisión final</span>
                </p>
                <div class="h-1.5 rounded-full bg-white/10 overflow-hidden mb-5">
                    <div class="h-full rounded-full transition-all duration-500" style="background: var(--color-accent)"
                         :style="`width: ${phase === 'review' ? 100 : ((index + 1) / total) * 100}%`"></div>
                </div>

                <ol class="hidden lg:block space-y-1 max-h-[60vh] overflow-y-auto pr-1">
                    <template x-for="(step, i) in steps" :key="step[0]">
                        <li class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-semibold"
                            :class="i < index || phase === 'review' ? 'text-white' : (i === index ? 'text-white bg-white/10' : 'text-purple-300/50')">
                            <span class="grid place-items-center w-5 h-5 rounded-full text-[10px] shrink-0"
                                  :class="i < index || phase === 'review' ? 'bg-[var(--color-accent)] text-[var(--color-ink)]' : (i === index ? 'border-2 border-[var(--color-accent)]' : 'border border-white/20')">
                                <span x-show="i < index || phase === 'review'" aria-hidden="true">✓</span>
                            </span>
                            <span x-text="step[1]"></span>
                        </li>
                    </template>
                </ol>
            </aside>

            {{-- Panel principal --}}
            <div class="rounded-[2rem] bg-white/[0.04] border border-white/10 p-6 sm:p-10">
                {{-- Orbe --}}
                <div class="flex justify-center mb-8">
                    <div class="relative w-28 h-28 grid place-items-center">
                        <template x-if="listening">
                            <div class="voice-orb-ring"></div>
                        </template>
                        <template x-if="listening">
                            <div class="voice-orb-ring delay-1"></div>
                        </template>
                        <template x-if="listening">
                            <div class="voice-orb-ring delay-2"></div>
                        </template>
                        <div class="relative grid place-items-center w-20 h-20 rounded-full transition-colors"
                             :style="listening ? 'background: var(--color-accent)' : 'background: var(--color-primary)'">
                            <x-icon name="mic" class="w-8 h-8" x-bind:class="listening ? 'text-[var(--color-ink)]' : 'text-white'" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-center gap-1 h-8 mb-6" aria-hidden="true" x-show="listening" x-cloak>
                    <span class="wave-bar h-3" style="animation-delay:0s"></span>
                    <span class="wave-bar h-6" style="animation-delay:.1s"></span>
                    <span class="wave-bar h-4" style="animation-delay:.2s"></span>
                    <span class="wave-bar h-8" style="animation-delay:.3s"></span>
                    <span class="wave-bar h-5" style="animation-delay:.15s"></span>
                    <span class="wave-bar h-3" style="animation-delay:.25s"></span>
                </div>

                {{-- Estado / pregunta --}}
                <div role="status" aria-live="polite" class="text-center min-h-[80px]">
                    <p class="text-2xl sm:text-3xl font-display font-bold text-white text-balance leading-tight" x-text="program || 'Presiona Comenzar para iniciar tu registro por voz.'"></p>
                    <p class="text-purple-200 mt-3 font-mono text-sm" x-show="message" x-text="message"></p>
                </div>

                {{-- Botones de voz --}}
                <div class="flex flex-wrap gap-3 justify-center mt-8">
                    <button type="button" @click="autoStart()" x-show="!running" class="btn btn-dark text-lg">
                        <x-icon name="play" class="w-5 h-5" /> Comenzar / Retomar
                    </button>
                    <button type="button" @click="startListening()" x-show="running && !listening" class="btn btn-dark">
                        <x-icon name="mic" class="w-5 h-5" /> Responder por voz
                    </button>
                    <button type="button" @click="stopListening()" x-show="listening" x-cloak class="btn btn-danger">
                        <span class="voice-fab-listening !w-3.5 !h-3.5" aria-hidden="true"></span> Detener escucha
                    </button>
                    <button type="button" @click="speak(program)" x-show="running" class="btn btn-secondary !bg-transparent !text-white !border-white/30 hover:!bg-white/10">
                        <x-icon name="refresh" class="w-5 h-5" /> Repetir
                    </button>
                    <button type="button" @click="goBack()" x-show="running" class="btn btn-secondary !bg-transparent !text-white !border-white/30 hover:!bg-white/10">
                        <x-icon name="chevron-right" class="w-5 h-5 rotate-180" /> Volver
                    </button>
                    <button type="button" @click="help()" x-show="running" class="btn btn-secondary !bg-transparent !text-white !border-white/30 hover:!bg-white/10">
                        <x-icon name="info" class="w-5 h-5" /> Ayuda
                    </button>
                    <button type="button" @click="exit()" x-show="running" class="btn btn-secondary !bg-transparent !text-white !border-white/30 hover:!bg-white/10">
                        <x-icon name="close" class="w-5 h-5" /> Salir
                    </button>
                </div>

                {{-- Alternativa por teclado --}}
                <div class="mt-8 pt-8 border-t border-white/10">
                    <p class="text-sm text-purple-200 text-center mb-3">
                        ⌨ ¿No encuentras el micrófono o prefieres el teclado? Escribe tu respuesta aquí.
                    </p>
                    <form @submit.prevent="sendTypeAnswer()" class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 max-w-xl mx-auto">
                        <label for="typed" class="sr-only">Respuesta por teclado</label>
                        <input id="typed" x-model="typedAnswer" type="text" placeholder="Escribe tu respuesta o un comando (ayuda, repetir, volver, salir, corregir)…"
                               class="input-text !bg-white/10 !border-white/20 !text-white placeholder:!text-purple-300" :disabled="!running">
                        <button type="submit" class="btn btn-dark" :disabled="!running">Enviar</button>
                    </form>
                </div>

                {{-- Comandos rápidos --}}
                <details class="mt-6 rounded-xl border border-white/10 p-4 text-white">
                    <summary class="font-semibold text-purple-200 cursor-pointer">Comandos de voz disponibles</summary>
                    <ul class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-sm text-purple-200">
                        <li class="flex items-center gap-2"><x-icon name="info" class="w-4 h-4 shrink-0" style="color: var(--color-accent)" /><strong class="text-white">Ayuda</strong> — explicación</li>
                        <li class="flex items-center gap-2"><x-icon name="refresh" class="w-4 h-4 shrink-0" style="color: var(--color-accent)" /><strong class="text-white">Repetir</strong> — repetir pregunta</li>
                        <li class="flex items-center gap-2"><x-icon name="chevron-right" class="w-4 h-4 shrink-0 rotate-180" style="color: var(--color-accent)" /><strong class="text-white">Volver</strong> — paso anterior</li>
                        <li class="flex items-center gap-2"><x-icon name="close" class="w-4 h-4 shrink-0" style="color: var(--color-accent)" /><strong class="text-white">Corregir</strong> — corregir dato</li>
                        <li class="flex items-center gap-2"><x-icon name="arrow-right" class="w-4 h-4 shrink-0" style="color: var(--color-accent)" /><strong class="text-white">Continuar</strong> — siguiente</li>
                        <li class="flex items-center gap-2"><x-icon name="close" class="w-4 h-4 shrink-0" style="color: var(--color-accent)" /><strong class="text-white">Salir/Cancelar</strong> — salir</li>
                    </ul>
                </details>

                {{-- Confirmación final --}}
                <div x-show="phase === 'review'" x-cloak class="mt-8 rounded-2xl border-2 p-5 sm:p-6 bg-white space-y-4 fade-in" style="border-color: var(--color-accent)">
                    <h2 class="font-display font-bold text-xl flex items-center gap-2.5" id="review-title">
                        <span class="grid place-items-center w-9 h-9 rounded-xl" style="background: var(--color-lavender); color: var(--color-primary)" aria-hidden="true"><x-icon name="check" class="w-4 h-4" /></span>
                        Revisa tus datos
                    </h2>
                    <dl id="review-list" class="text-sm space-y-2"></dl>
                    <label for="pass" class="input-label">Crea una contraseña para tu cuenta (mínimo 8 caracteres)</label>
                    <input type="password" id="pass" x-model="password" class="input-text" minlength="8" required>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" @click="confirm()" class="btn btn-primary">
                            <x-icon name="check" class="w-5 h-5" /> Confirmar y crear cuenta
                        </button>
                        <button type="button" @click="start()" class="btn btn-secondary">Volver a empezar</button>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-purple-300 mt-8">
            Tu progreso se guarda automáticamente. Si interrumpes, puedes retomarlo cuando vuelvas.
            Atajo: <kbd class="px-1.5 py-0.5 rounded bg-white/10 border border-white/20">Alt</kbd>+<kbd class="px-1.5 py-0.5 rounded bg-white/10 border border-white/20">V</kbd> activa el asistente del sitio.
        </p>
    </div>
</div>
@endsection
