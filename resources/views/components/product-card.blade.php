@props(['publication'])

{{-- Tarjeta compacta del home: flyer completo (object-contain, sin recortar el texto), nombre, categoría, precio y "Ver más". --}}
@php($url = route('public.publication', $publication->slug))
<article class="group flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white transition-colors hover:border-purple-400 hover:bg-purple-100">
    <a href="{{ $url }}" class="relative block aspect-[3/4] bg-gray-50" tabindex="-1" aria-hidden="true">
        @if ($publication->flyer_image)
            <img src="{{ asset($publication->flyer_image) }}" alt="" loading="lazy"
                 class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-[1.03]">
        @else
            <span class="grid h-full w-full place-items-center text-purple-400"><x-icon name="box" class="h-12 w-12" /></span>
        @endif
        @if ($publication->business?->entrepreneurProfile?->isVerified())
            <span class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full bg-purple-600 px-2 py-0.5 text-[11px] font-bold text-white shadow-sm">
                <x-icon name="verified" class="h-3.5 w-3.5" /> Verificado
            </span>
        @endif
    </a>

    <div class="flex flex-1 flex-col gap-1 p-3">
        <p class="truncate text-xs font-semibold uppercase tracking-wide text-gray-600">{{ $publication->business?->categoryLabel() ?? 'General' }}</p>
        <h3 class="line-clamp-2 text-sm font-bold leading-snug text-gray-900 sm:text-base" style="font-family: inherit; letter-spacing: 0">
            <a href="{{ $url }}" class="hover:text-purple-700">{{ $publication->name }}</a>
        </h3>
        <p class="mt-auto pt-1 text-base font-extrabold text-purple-700">{{ $publication->price_display }}</p>
        <a href="{{ $url }}" class="mt-2 inline-flex min-h-[2.5rem] items-center justify-center rounded-full bg-purple-600 px-3 text-sm font-bold text-white hover:bg-purple-700"
           aria-label="Ver más sobre {{ $publication->name }}">
            Ver más
        </a>
    </div>
</article>
