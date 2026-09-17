@extends('layouts.app')

@section('title', $publication->name)

@section('content')
@php
    $gallery = collect([$publication->flyer_image ? ['path' => $publication->flyer_image] : null])
        ->filter()
        ->merge($publication->images->map(fn ($img) => ['path' => $img->path]))
        ->values();
    $altText = 'Imagen de '.$publication->name.', '.($publication->business->category?->name ?? 'emprendimiento').' en '.$publication->business->location_summary;
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="{ activeImg: 0 }">
    <nav aria-label="Migas de pan" class="text-sm mb-6" style="color: var(--color-muted)">
        <ol class="flex flex-wrap items-center gap-1">
            <li><a href="{{ route('public.home') }}" class="hover:text-purple-700">Inicio</a></li>
            <li aria-hidden="true"><x-icon name="chevron-right" class="w-4 h-4" /></li>
            <li><a href="{{ route('public.explore') }}" class="hover:text-purple-700">Explorar</a></li>
            <li aria-hidden="true"><x-icon name="chevron-right" class="w-4 h-4" /></li>
            <li><a href="{{ route('public.business', $publication->business->slug) }}" class="hover:text-purple-700 truncate max-w-[160px]">{{ $publication->business->name }}</a></li>
            <li aria-hidden="true"><x-icon name="chevron-right" class="w-4 h-4" /></li>
            <li aria-current="page" class="font-semibold text-ink truncate max-w-[160px]">{{ $publication->name }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        {{-- Galería --}}
        <div class="lg:sticky lg:top-24 self-start space-y-3">
            <div class="card overflow-hidden aspect-square">
                @if ($gallery->isNotEmpty())
                    @foreach ($gallery as $i => $img)
                        <img x-show="activeImg === {{ $i }}" src="{{ asset($img['path']) }}" alt="{{ $altText }}" class="w-full h-full object-cover">
                    @endforeach
                @else
                    <div class="w-full h-full grid place-items-center" style="background: var(--color-lavender); color: var(--color-primary)" aria-hidden="true">
                        <x-icon name="box" class="w-20 h-20" />
                    </div>
                @endif
            </div>

            @if ($gallery->count() > 1)
                <div class="grid grid-cols-4 gap-3" role="tablist" aria-label="Miniaturas de imágenes">
                    @foreach ($gallery as $i => $img)
                        <button type="button" role="tab" @click="activeImg = {{ $i }}" :aria-selected="(activeImg === {{ $i }}).toString()"
                                class="aspect-square rounded-xl overflow-hidden border-2 transition-colors"
                                :class="activeImg === {{ $i }} ? 'border-purple-600' : 'border-transparent'">
                            <img src="{{ asset($img['path']) }}" alt="Miniatura {{ $i + 1 }} de {{ $publication->name }}" class="w-full h-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Información --}}
        <div class="space-y-6">
            <div class="flex flex-wrap items-center gap-2">
                @if ($publication->business?->entrepreneurProfile?->isVerified())
                    <p class="badge badge-approved" aria-label="Emprendimiento verificado">
                        <x-icon name="verified" class="w-4 h-4" /> Verificado
                    </p>
                @endif
                <p class="badge badge-neutral">{{ ucfirst($publication->type) }}</p>
                <p class="badge badge-draft">{{ $publication->business->category?->name ?? 'General' }}</p>
            </div>

            <div>
                <p class="mono-label mb-2">{{ $publication->business->name }}</p>
                <h1 class="text-4xl sm:text-5xl font-display font-extrabold leading-[0.95] text-balance">{{ $publication->name }}</h1>
            </div>

            <p class="text-4xl font-display font-extrabold text-purple-600">{{ $publication->price_display }}</p>
            <p class="text-gray-700 leading-relaxed text-lg">{{ $publication->description }}</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="rounded-2xl p-5" style="background-color: var(--color-cream)">
                    <p class="mono-label mb-2 flex items-center gap-1.5"><x-icon name="clock" class="w-4 h-4 text-purple-600" /> Horario</p>
                    <p class="font-semibold text-ink">{{ $publication->business->schedule_summary }}</p>
                </div>
                <div class="rounded-2xl p-5" style="background-color: var(--color-cream)">
                    <p class="mono-label mb-2 flex items-center gap-1.5"><x-icon name="map-pin" class="w-4 h-4 text-purple-600" /> Ubicación</p>
                    <p class="font-semibold text-ink">{{ $publication->business->location_summary }}</p>
                </div>
            </div>

            {{-- Reproductor de descripción por voz --}}
            <div class="rounded-2xl p-4 flex items-center gap-4 text-white" style="background-color: var(--color-ink)"
                 x-data="{ playing: false, toggle() {
                    if (!window.speechSynthesis) return;
                    if (this.playing) { window.speechSynthesis.cancel(); this.playing = false; return; }
                    const u = new SpeechSynthesisUtterance({{ Illuminate\Support\Js::from($publication->name.'. '.$publication->description) }});
                    const voice = window.sonaraPickVoice ? window.sonaraPickVoice(window.speechSynthesis, 'es') : null;
                    const prefs = window.sonaraVoicePrefs ? window.sonaraVoicePrefs() : { rate: 0.94, pitch: 1.04, volume: 1 };
                    u.lang = (voice && voice.lang) || 'es-419';
                    if (voice) u.voice = voice;
                    u.rate = prefs.rate; u.pitch = prefs.pitch; u.volume = prefs.volume;
                    u.onend = () => this.playing = false;
                    window.speechSynthesis.speak(u); this.playing = true;
                 } }">
                <button type="button" @click="toggle()" class="shrink-0 grid place-items-center w-11 h-11 rounded-full" style="background: var(--color-accent); color: var(--color-ink)" :aria-label="playing ? 'Detener lectura' : 'Escuchar descripción'">
                    <x-icon name="play" x-show="!playing" class="w-5 h-5" />
                    <x-icon name="close" x-show="playing" x-cloak class="w-5 h-5" />
                </button>
                <div class="min-w-0">
                    <p class="font-bold text-sm">Escuchar descripción</p>
                    <p class="text-xs text-white/60 truncate" x-text="playing ? 'Reproduciendo…' : 'Toca para escuchar en voz alta'"></p>
                </div>
                <div class="ml-auto flex items-end gap-0.5 h-6" aria-hidden="true" x-show="playing">
                    <span class="wave-bar h-3" style="animation-delay:0s"></span>
                    <span class="wave-bar h-5" style="animation-delay:.1s"></span>
                    <span class="wave-bar h-2" style="animation-delay:.2s"></span>
                    <span class="wave-bar h-6" style="animation-delay:.3s"></span>
                    <span class="wave-bar h-3" style="animation-delay:.4s"></span>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                @if ($publication->business->phone)
                    <a href="tel:{{ $publication->business->phone }}" class="btn btn-secondary">
                        <x-icon name="phone" class="w-5 h-5" /> Llamar
                    </a>
                @endif
                @if ($publication->business->whatsapp)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $publication->business->whatsapp) }}?text={{ urlencode('Hola, me interesa: '.$publication->name.' ('. $publication->price_display .') desde SONARA.') }}"
                       target="_blank" rel="noopener" class="btn btn-success">
                        <x-icon name="chat" class="w-5 h-5" /> WhatsApp
                    </a>
                @endif
            </div>

            {{-- Solicitud --}}
            <section id="solicitar" aria-labelledby="request-form-title" class="card card-body !p-6 sm:!p-8 scroll-mt-24">
                <h2 id="request-form-title" class="text-xl font-display font-bold mb-5 flex items-center gap-2.5">
                    <span class="grid place-items-center w-9 h-9 rounded-xl" style="background: var(--color-lavender); color: var(--color-primary)" aria-hidden="true"><x-icon name="chat" class="w-4 h-4" /></span>
                    Enviar solicitud
                </h2>

                @auth
                    @if (auth()->user()->isCustomer())
                        <form action="{{ route('customer.requests.store') }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="publication_id" value="{{ $publication->id }}">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="r-name" class="input-label">Tu nombre</label>
                                    <input type="text" id="r-name" name="name" required class="input-text @error('name') input-error @enderror"
                                           value="{{ old('name', auth()->user()->name) }}">
                                    @error('name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="r-phone" class="input-label">Tu teléfono</label>
                                    <input type="tel" id="r-phone" name="phone" required class="input-text @error('phone') input-error @enderror"
                                           value="{{ old('phone', auth()->user()->phone) }}">
                                    @error('phone')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label for="r-quantity" class="input-label">Cantidad</label>
                                    <input type="number" id="r-quantity" name="quantity" min="1" value="{{ old('quantity', 1) }}" class="input-text @error('quantity') input-error @enderror">
                                    @error('quantity')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="r-date" class="input-label">Fecha preferida</label>
                                    <input type="date" id="r-date" name="preferred_date" class="input-text" value="{{ old('preferred_date') }}">
                                </div>
                                <div>
                                    <label for="r-time" class="input-label">Hora preferida</label>
                                    <input type="time" id="r-time" name="preferred_time" class="input-text" value="{{ old('preferred_time') }}">
                                </div>
                            </div>

                            <div>
                                <label for="r-message" class="input-label">Mensaje adicional (opcional)</label>
                                <textarea id="r-message" name="message" rows="3" class="input-text" placeholder="Cuéntale al emprendedor lo que necesitas…">{{ old('message') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-full">Enviar solicitud</button>
                        </form>
                    @else
                        <div class="text-center py-4">
                            <p class="text-gray-600">Para solicitar este producto o servicio necesitas una cuenta de cliente.</p>
                            <a href="{{ route('register') }}" class="btn btn-primary mt-4">Crear cuenta de cliente</a>
                        </div>
                    @endif
                @else
                    <div class="text-center py-4">
                        <p class="text-gray-600">Inicia sesión para enviar una solicitud personalizada al emprendedor.</p>
                        <div class="flex gap-3 justify-center mt-4">
                            <a href="{{ route('login') }}" class="btn btn-primary">Iniciar sesión</a>
                            <a href="{{ route('register') }}" class="btn btn-secondary">Registrarse</a>
                        </div>
                    </div>
                @endauth
            </section>
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section aria-labelledby="related-title" class="mt-20">
            <p class="mono-label mb-2">También te puede interesar</p>
            <h2 id="related-title" class="section-title mb-6">Publicaciones relacionadas</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ($related as $item)
                    <x-publication-card :publication="$item" />
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
