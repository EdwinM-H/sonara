@props(['publication'])

<article class="card overflow-hidden flex flex-col h-full group card-hover">
    <a href="{{ route('public.publication', $publication->slug) }}" class="relative block aspect-[16/10] overflow-hidden">
        @if ($publication->flyer_image)
            <img src="{{ asset($publication->flyer_image) }}" alt="Producto o servicio: {{ $publication->name }}"
                 class="w-full h-full object-cover group-hover:scale-[1.05] transition-transform duration-500">
        @else
            <div class="w-full h-full grid place-items-center" style="background: var(--color-lavender); color: var(--color-primary)" aria-hidden="true">
                <x-icon name="box" class="w-12 h-12" />
            </div>
        @endif

        @if ($publication->business?->entrepreneurProfile?->isVerified())
            <p class="badge badge-approved absolute top-3 left-3 !bg-white shadow-soft" aria-label="Emprendimiento verificado">
                <x-icon name="verified" class="w-4 h-4" /> Verificado
            </p>
        @endif

        <p class="absolute bottom-3 right-3 rounded-xl text-white text-sm font-bold px-3 py-1.5 shadow-sm backdrop-blur" style="background-color: rgba(20,16,31,0.85)">
            {{ $publication->price_display }}
        </p>
    </a>

    <div class="card-body flex-1 flex flex-col gap-2 !p-4">
        <p class="mono-label">{{ $publication->business?->category?->name ?? 'General' }}</p>

        <h3 class="font-display font-bold text-lg leading-snug">
            <a href="{{ route('public.publication', $publication->slug) }}" class="hover:text-purple-700 focus-visible:rounded">
                {{ $publication->name }}
            </a>
        </h3>

        <p class="text-sm text-gray-600 line-clamp-2">{{ $publication->description }}</p>

        <dl class="mt-1 space-y-1.5 text-sm" style="color: var(--color-muted)">
            <div class="flex items-center gap-1.5">
                <dt class="sr-only">Emprendimiento</dt>
                <x-icon name="tag" class="w-4 h-4 text-purple-500 shrink-0" />
                <dd>{{ $publication->business?->name }}</dd>
            </div>
            @if ($publication->business?->location_summary)
                <div class="flex items-center gap-1.5">
                    <dt class="sr-only">Ubicación</dt>
                    <x-icon name="map-pin" class="w-4 h-4 text-purple-500 shrink-0" />
                    <dd>{{ $publication->business->location_summary }}</dd>
                </div>
            @endif
        </dl>

        <div class="mt-auto pt-3">
            <a href="{{ route('public.publication', $publication->slug) }}" class="btn btn-outline btn-sm w-full">
                Ver detalle
                <x-icon name="arrow-right" class="w-4 h-4" />
            </a>
        </div>
    </div>
</article>
