{{-- Menú por voz del dashboard (usuarios con navegación por voz). --}}
<section x-data="voiceAssistant" data-standalone-voice
         data-mode="dashboard"
         data-prompt="{{ \App\Services\Assistant\EntrepreneurVoiceMenu::PROMPT }}"
         data-process-route="{{ route('entrepreneur.voice.command') }}"
         aria-label="Asistente de voz"
         class="rounded-2xl mb-6 p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-4 text-white"
         style="background-color: var(--color-ink)">
    <span class="grid place-items-center w-14 h-14 rounded-full shrink-0 transition-colors"
          :style="listening ? 'background: var(--color-accent)' : 'background: var(--color-primary)'" aria-hidden="true">
        <x-icon name="mic" class="w-6 h-6" x-bind:class="listening ? 'text-[var(--color-ink)]' : 'text-white'" />
    </span>
    <div role="status" aria-live="polite" class="flex-1 min-w-0">
        <p class="font-display font-bold text-lg text-balance"
           x-text="program || 'Presione cualquier tecla para activar el asistente de voz.'"></p>
        <p class="text-purple-200 mt-1 font-mono text-sm" x-show="message" x-text="message"></p>
    </div>
    <div class="flex gap-2 shrink-0">
        <button type="button" @click="restart()" x-show="!running" class="btn btn-dark">
            <x-icon name="play" class="w-5 h-5" /> Activar voz
        </button>
        <button type="button" @click="repeat()" x-show="running" x-cloak class="btn btn-secondary !bg-transparent !text-white !border-white/30 hover:!bg-white/10">
            <x-icon name="refresh" class="w-5 h-5" /> Repetir
        </button>
    </div>
</section>
