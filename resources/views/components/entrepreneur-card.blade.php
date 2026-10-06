@props(['profile'])

{{-- Avatar, nombre y enlace al perfil público del emprendedor. --}}
@php
    $fullName = \Illuminate\Support\Str::title(trim(($profile->user?->first_name ?? '').' '.($profile->user?->last_name ?? '')) ?: ($profile->user?->name ?? 'Emprendedor'));
    $initials = collect(explode(' ', $fullName))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
@endphp
<section aria-label="Emprendedor" {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-4 rounded-2xl border border-gray-200 bg-white p-4']) }}>
    <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-purple-100 text-lg font-extrabold text-purple-800" aria-hidden="true">{{ $initials }}</span>
    <div class="min-w-0 flex-1">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-600">Emprendedor</p>
        <p class="truncate text-lg font-bold text-gray-900">{{ $fullName }}</p>
        @if ($profile->isVerified())
            <p class="inline-flex items-center gap-1 text-sm font-semibold text-purple-600"><x-icon name="verified" class="h-4 w-4" /> Verificado</p>
        @endif
    </div>
    <a href="{{ route('public.entrepreneur', $profile) }}" class="btn btn-outline btn-sm w-full sm:w-auto">
        Ver perfil <x-icon name="arrow-right" class="h-4 w-4" />
    </a>
</section>
