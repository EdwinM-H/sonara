@extends('layouts.app')

@section('title', $business->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav aria-label="Migas de pan" class="text-sm text-gray-500 mb-6">
        <ol class="flex flex-wrap items-center gap-1">
            <li><a href="{{ route('public.home') }}" class="hover:text-purple-700">Inicio</a></li>
            <li aria-hidden="true"><x-icon name="chevron-right" class="w-4 h-4" /></li>
            <li><a href="{{ route('public.explore') }}" class="hover:text-purple-700">Explorar</a></li>
            <li aria-hidden="true"><x-icon name="chevron-right" class="w-4 h-4" /></li>
            <li aria-current="page" class="text-gray-800 font-medium truncate">{{ $business->name }}</li>
        </ol>
    </nav>

    {{-- Vitrina del emprendimiento --}}
    <div class="card overflow-hidden mb-10">
        <div class="h-28 sm:h-36 relative overflow-hidden" style="background-color: var(--color-primary-deep)" aria-hidden="true">
            <div class="absolute -top-10 -right-10 w-56 h-56 rounded-full opacity-30 blur-3xl" style="background: var(--color-primary)"></div>
            @if ($business->entrepreneurProfile?->isVerified())
                <p class="badge badge-approved !bg-white mt-4 ml-4 shadow-sm w-fit relative" aria-label="Emprendedor verificado">
                    <x-icon name="verified" class="w-4 h-4" /> Emprendedor verificado
                </p>
            @endif
        </div>

        <div class="card-body !p-5 sm:!p-8 sm:pt-4">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                <div class="max-w-2xl">
                    <p class="mono-label mb-2">{{ $business->category?->name }}</p>
                    <h1 class="text-3xl sm:text-4xl font-display font-extrabold">{{ $business->name }}</h1>
                    <p class="mt-2 text-gray-600 flex items-center gap-1.5">
                        <x-icon name="tag" class="w-4 h-4 text-purple-500" />
                        {{ $business->category?->name }}<span class="text-gray-300">·</span>{{ $business->subcategory?->name ?? 'General' }}
                    </p>
                    <p class="mt-4 text-gray-700 leading-relaxed">{{ $business->description }}</p>

                    <dl class="mt-5 space-y-2 text-sm text-gray-700">
                        <div class="flex items-center gap-2">
                            <dt class="sr-only">Precio de referencia</dt>
                            <x-icon name="credit-card" class="w-4 h-4 text-purple-500 shrink-0" />
                            <dd class="font-bold text-purple-800 text-base">{{ $business->currency === 'PEN' ? 'S/' : $business->currency }} {{ $business->display_price }}</dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <dt class="sr-only">Ubicación</dt>
                            <x-icon name="map-pin" class="w-4 h-4 text-purple-500 shrink-0" />
                            <dd>{{ $business->location_summary }}</dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <dt class="sr-only">Horario</dt>
                            <x-icon name="clock" class="w-4 h-4 text-purple-500 shrink-0" />
                            <dd>{{ $business->schedule_summary }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="lg:w-72 shrink-0 space-y-3">
                    @if ($business->phone)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $business->phone) }}"
                           class="btn btn-secondary w-full justify-start" aria-label="Llamar al {{ $business->phone }}">
                            <x-icon name="phone" class="w-5 h-5" /> Llamar
                        </a>
                    @endif
                    @if ($business->whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $business->whatsapp) }}?text={{ urlencode('Hola, vi tu emprendimiento '.$business->name.' en SONARA.') }}"
                           target="_blank" rel="noopener" class="btn btn-success w-full justify-start" aria-label="Escribir por WhatsApp al {{ $business->whatsapp }}">
                            <x-icon name="chat" class="w-5 h-5" /> WhatsApp
                        </a>
                    @endif
                    @if ($business->phone)
                        <button type="button" data-copy="{{ $business->phone }}" class="btn btn-secondary w-full justify-start copy-number"
                                aria-label="Copiar número {{ $business->phone }}">
                            <x-icon name="clipboard" class="w-5 h-5" /> Copiar número
                        </button>
                    @endif
                    @if ($business->contact_email)
                        <p class="text-sm text-gray-600 pt-2 flex items-start gap-1.5">
                            <x-icon name="user" class="w-4 h-4 text-purple-500 shrink-0 mt-0.5" />
                            <a href="mailto:{{ $business->contact_email }}" class="text-purple-700 hover:underline break-all">{{ $business->contact_email }}</a>
                        </p>
                    @endif
                </div>
            </div>

            {{-- Horario --}}
            @if ($business->hours->isNotEmpty())
                <div class="mt-8 border-t border-gray-100 pt-6">
                    <h2 class="text-base font-bold mb-3 flex items-center gap-2">
                        <x-icon name="clock" class="w-5 h-5 text-purple-600" /> Horario de atención
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-w-3xl">
                        @foreach ($business->hours as $hour)
                            <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-2.5 text-sm">
                                <span class="font-semibold">{{ $hour->day_name }}</span>
                                <span class="{{ $hour->is_closed ? 'text-red-600 font-medium' : 'text-gray-700' }}">
                                    @if ($hour->is_closed)
                                        Cerrado
                                    @else
                                        {{ \Illuminate\Support\Carbon::parse($hour->open_time)->format('H:i') }} – {{ \Illuminate\Support\Carbon::parse($hour->close_time)->format('H:i') }}
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Publicaciones --}}
    <section aria-labelledby="business-publicaciones">
        <h2 id="business-publicaciones" class="section-title mb-6">Productos y servicios</h2>

        @if ($published->isEmpty())
            <div class="empty-state" role="status">
                <span class="empty-icon"><x-icon name="box" /></span>
                <p class="mt-3 font-semibold text-gray-800">Este emprendimiento aún no tiene publicaciones</p>
                <p class="text-sm text-gray-600 mt-1">Cuando publique, aparecerán aquí.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($published as $publication)
                    <x-publication-card :publication="$publication" />
                @endforeach
            </div>
        @endif
    </section>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.copy-number').forEach(el => {
        el.addEventListener('click', () => {
            const value = el.dataset.copy;
            navigator.clipboard.writeText(value).then(() => {
                const original = el.innerHTML;
                el.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5" aria-hidden="true"><path d="M4.5 12.75l6 6 9-13.5" /></svg> Copiado';
                setTimeout(() => el.innerHTML = original, 2000);
            });
        });
    });
</script>
@endpush
@endsection