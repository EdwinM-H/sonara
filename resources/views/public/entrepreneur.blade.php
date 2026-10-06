@extends('layouts.app')

@php
    $user = $profile->user;
    $fullName = \Illuminate\Support\Str::title(trim($user->first_name.' '.$user->last_name) ?: $user->name);
    $initials = collect(explode(' ', $fullName))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
    // Resumen de lo que ofrece: categorías y etiquetas de sus emprendimientos, sin repetir.
    $offers = $businesses->map(fn ($b) => $b->categoryLabel())
        ->merge($businesses->flatMap(fn ($b) => $b->tags ?? []))
        ->filter()->map(fn ($v) => \Illuminate\Support\Str::ucfirst($v))->unique()->values();
    $types = $businesses->pluck('type')->filter()->unique()->map(fn ($t) => $t === 'servicio' ? 'servicios' : 'productos');
@endphp

@section('title', $fullName)

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <nav aria-label="Migas de pan" class="mb-6 text-sm text-gray-600">
        <ol class="flex flex-wrap items-center gap-1">
            <li><a href="{{ route('public.home') }}" class="hover:text-purple-700">Inicio</a></li>
            <li aria-hidden="true"><x-icon name="chevron-right" class="h-4 w-4" /></li>
            <li aria-current="page" class="font-semibold text-gray-900">{{ $fullName }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Ficha --}}
        <section aria-labelledby="perfil-nombre" class="rounded-3xl border border-gray-200 bg-white p-6 lg:col-span-1 lg:self-start">
            <span class="grid h-20 w-20 place-items-center rounded-full bg-purple-100 text-2xl font-extrabold text-purple-800" aria-hidden="true">{{ $initials }}</span>
            <h1 id="perfil-nombre" class="mt-4 text-2xl font-extrabold sm:text-3xl">{{ $fullName }}</h1>

            @if ($profile->isVerified())
                <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-purple-600 px-3 py-1 text-sm font-bold text-white">
                    <x-icon name="verified" class="h-4 w-4" /> Verificado
                </p>
            @else
                <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-sm font-bold text-gray-700 ring-1 ring-gray-200">
                    No verificado
                </p>
            @endif

            @if ($profile->location)
                <p class="mt-4 flex items-start gap-2 text-gray-700">
                    <x-icon name="map-pin" class="mt-0.5 h-5 w-5 shrink-0 text-purple-700" />
                    <span><span class="sr-only">Ubicación: </span>{{ \Illuminate\Support\Str::title($profile->location) }}</span>
                </p>
            @endif

            <h2 class="mt-6 text-base font-bold">Sobre mí</h2>
            <p class="mt-1 leading-relaxed text-gray-700">{{ $profile->personal_description ? \Illuminate\Support\Str::ucfirst($profile->personal_description) : 'Este emprendedor aún no agregó una descripción.' }}</p>

            @if ($offers->isNotEmpty() || $types->isNotEmpty())
                <h2 class="mt-6 text-base font-bold">Qué ofrece</h2>
                @if ($types->isNotEmpty())
                    <p class="mt-1 text-gray-700">{{ \Illuminate\Support\Str::ucfirst($types->implode(' y ')) }}:</p>
                @endif
                <ul class="mt-2 flex flex-wrap gap-2" aria-label="Productos y servicios que ofrece">
                    @foreach ($offers as $offer)
                        <li class="rounded-full bg-purple-100 px-3 py-1 text-sm font-semibold text-purple-800 ring-1 ring-purple-200">{{ $offer }}</li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Emprendimientos --}}
        <section aria-labelledby="perfil-emprendimientos" class="lg:col-span-2">
            <h2 id="perfil-emprendimientos" class="mb-4 text-xl font-extrabold sm:text-2xl">
                Emprendimientos publicados <span class="text-gray-600">({{ $businesses->count() }})</span>
            </h2>

            @if ($businesses->isEmpty())
                <p class="rounded-2xl border border-dashed border-gray-300 p-8 text-center text-gray-600">Aún no tiene emprendimientos publicados.</p>
            @else
                <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ($businesses as $business)
                        @php($flyer = $business->adPublication?->flyer_image)
                        <li>
                            <a href="{{ route('public.business', $business) }}" class="group flex h-full gap-4 rounded-2xl border border-gray-200 bg-white p-3 transition-colors hover:border-purple-400 hover:bg-purple-100">
                                <span class="block aspect-[3/4] w-24 shrink-0 overflow-hidden rounded-xl bg-gray-50" aria-hidden="true">
                                    @if ($flyer)
                                        <img src="{{ asset($flyer) }}" alt="" loading="lazy" class="h-full w-full object-contain">
                                    @else
                                        <span class="grid h-full w-full place-items-center text-purple-700"><x-icon name="box" class="h-8 w-8" /></span>
                                    @endif
                                </span>
                                <span class="flex min-w-0 flex-col gap-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-600">{{ $business->categoryLabel() ?? 'General' }}</span>
                                    <span class="font-bold leading-snug text-gray-900 group-hover:text-purple-700">{{ \Illuminate\Support\Str::ucfirst($business->name) }}</span>
                                    <span class="line-clamp-2 text-sm text-gray-600">{{ $business->description }}</span>
                                    <span class="mt-auto text-sm font-extrabold text-purple-700">{{ $business->adPublication?->price_display ?? ($business->price_text ?: 'Precio a consultar') }}</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</div>
@endsection
