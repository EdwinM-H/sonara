@extends('layouts.panel-entrepreneur')

@section('panel-content')
    <x-panel-header title="Preferencias de accesibilidad" subtitle="Configura SONARA a tu medida: más contraste, letra más grande o lectura guiada." />

    @if ($preferences)
        <div role="status" class="rounded-2xl mb-6 border-[1.5px] p-4 text-sm flex items-center gap-3" style="background-color: var(--color-success-soft); border-color: var(--color-success-border); color: var(--color-success)">
            <x-icon name="verified" class="w-5 h-5 shrink-0" />
            Tienes preferencias guardadas. Se aplican al instante en todo el sitio, incluida la voz guiada.
        </div>
    @endif

    <div class="card card-body max-w-2xl !p-6 sm:!p-8">
        <form method="POST" action="{{ route('entrepreneur.accessibility.update') }}" class="space-y-7">
            @csrf
            @method('PATCH')

            <fieldset>
                <legend class="input-label mb-2">Velocidad de lectura (voz)</legend>
                <div class="grid grid-cols-3 gap-2">
                    @foreach (['lenta' => 'Lenta', 'normal' => 'Normal', 'rapida' => 'Rápida'] as $value => $label)
                        <label class="chip justify-center cursor-pointer {{ old('speech_rate', $preferences->speech_rate ?? 'normal') === $value ? 'chip-active' : '' }}">
                            <input type="radio" name="speech_rate" value="{{ $value }}" class="sr-only" @checked(old('speech_rate', $preferences->speech_rate ?? 'normal') === $value)>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('speech_rate')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </fieldset>

            <fieldset>
                <legend class="input-label mb-2">Volumen (voz)</legend>
                <div class="grid grid-cols-3 gap-2">
                    @foreach (['bajo' => 'Bajo', 'normal' => 'Normal', 'alto' => 'Alto'] as $value => $label)
                        <label class="chip justify-center cursor-pointer {{ old('volume', $preferences->volume ?? 'normal') === $value ? 'chip-active' : '' }}">
                            <input type="radio" name="volume" value="{{ $value }}" class="sr-only" @checked(old('volume', $preferences->volume ?? 'normal') === $value)>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <fieldset>
                <legend class="input-label mb-2">Tamaño de letra</legend>
                <div class="grid grid-cols-3 gap-2">
                    @foreach (['pequena' => 'Pequeña', 'normal' => 'Normal', 'grande' => 'Grande'] as $value => $label)
                        <label class="chip justify-center cursor-pointer {{ old('font_size', $preferences->font_size ?? 'normal') === $value ? 'chip-active' : '' }}">
                            <input type="radio" name="font_size" value="{{ $value }}" class="sr-only" @checked(old('font_size', $preferences->font_size ?? 'normal') === $value)>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <fieldset>
                <legend class="input-label mb-2">Modo de navegación</legend>
                <div class="grid grid-cols-3 gap-2">
                    @foreach (['visual' => 'Visual', 'voz' => 'Voz', 'mixto' => 'Mixto'] as $value => $label)
                        <label class="chip justify-center cursor-pointer {{ old('navigation_mode', $preferences->navigation_mode ?? 'mixto') === $value ? 'chip-active' : '' }}">
                            <input type="radio" name="navigation_mode" value="{{ $value }}" class="sr-only" @checked(old('navigation_mode', $preferences->navigation_mode ?? 'mixto') === $value)>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="space-y-3 pt-2 border-t-[1.5px]" style="border-color: var(--color-border)">
                <label class="flex items-center gap-3 pt-3">
                    <input type="checkbox" name="high_contrast" value="1" @checked($preferences->high_contrast ?? false) class="w-5 h-5 rounded text-purple-600 focus:ring-purple-500">
                    <span class="font-semibold">Alto contraste</span>
                </label>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="auto_read" value="1" @checked($preferences->auto_read ?? false) class="w-5 h-5 rounded text-purple-600 focus:ring-purple-500">
                    <span class="font-semibold">Leer en voz alta las instrucciones automáticamente</span>
                </label>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="repeat_prompts" value="1" @checked($preferences->repeat_prompts ?? false) class="w-5 h-5 rounded text-purple-600 focus:ring-purple-500">
                    <span class="font-semibold">Permitir repetir las instrucciones tantas veces como quiera</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary">Guardar preferencias</button>
        </form>
    </div>
@endsection
