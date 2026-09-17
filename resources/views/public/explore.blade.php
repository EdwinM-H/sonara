@extends('layouts.app')

@section('title', 'Explorar emprendimientos')

@section('content')
@php
    $activeFilters = collect(['q', 'category', 'subcategory', 'type', 'region', 'district', 'price_min', 'price_max'])
        ->filter(fn ($f) => request()->filled($f))->count();
    $currentCategory = $categories->firstWhere('id', (int) request('category'));
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="{ filtrosOpen: false }">
    <nav aria-label="Migas de pan" class="text-sm mb-4" style="color: var(--color-muted)">
        <ol class="flex flex-wrap items-center gap-1">
            <li><a href="{{ route('public.home') }}" class="hover:text-purple-700">Inicio</a></li>
            <li aria-hidden="true"><x-icon name="chevron-right" class="w-4 h-4" /></li>
            <li aria-current="page" class="font-semibold text-ink">Explorar</li>
        </ol>
    </nav>

    <header class="mb-8">
        <p class="mono-label mb-2">Marketplace</p>
        <h1 class="section-title">Explorar emprendimientos</h1>
        <p class="text-gray-600 mt-2 text-lg">Encuentra productos y servicios de emprendedores de tu comunidad.</p>
    </header>

    {{-- Botón de filtros móvil --}}
    <div class="flex items-center justify-between lg:hidden mb-4">
        <button type="button" class="btn btn-secondary btn-sm" @click="filtrosOpen = !filtrosOpen" :aria-expanded="filtrosOpen.toString()" aria-controls="filtros-movil">
            <x-icon name="filter" class="w-4 h-4" />
            Filtros
            @if ($activeFilters > 0)<span class="badge badge-error !py-0.5 !px-2">{{ $activeFilters }}</span>@endif
        </button>
        <p class="text-sm font-semibold" role="status" aria-live="polite">{{ $publications->total() }} resultados</p>
    </div>

    <div class="lg:grid lg:grid-cols-[300px_1fr] lg:gap-10 lg:items-start">
        {{-- Sidebar de filtros --}}
        <aside id="filtros-movil" x-show="filtrosOpen" x-cloak class="lg:!block lg:sticky lg:top-24 mb-6 lg:mb-0" aria-label="Filtros de búsqueda">
            <form action="{{ route('public.explore') }}" method="GET" class="card card-body !p-5 space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="font-display font-bold text-lg">Filtros</h2>
                    <a href="{{ route('public.explore') }}" class="text-sm font-bold text-purple-600 hover:underline">Limpiar</a>
                </div>

                <div>
                    <label for="f-q" class="input-label">Buscar</label>
                    <div class="input-group">
                        <span class="input-icon"><x-icon name="search" class="w-4 h-4" /></span>
                        <input type="search" id="f-q" name="q" value="{{ request('q') }}" placeholder="Producto, servicio…" class="input-text">
                    </div>
                </div>

                <fieldset>
                    <legend class="input-label mb-2">Categoría</legend>
                    <div class="space-y-2 max-h-52 overflow-y-auto pr-1">
                        <label class="flex items-center gap-2.5 text-sm font-medium">
                            <input type="radio" name="category" value="" class="rounded text-purple-600 focus:ring-purple-500" @checked(! request('category'))>
                            Todas
                        </label>
                        @foreach ($categories as $category)
                            <label class="flex items-center gap-2.5 text-sm font-medium">
                                <input type="radio" name="category" value="{{ $category->id }}" class="rounded text-purple-600 focus:ring-purple-500" @checked(request('category') == $category->id)>
                                <span aria-hidden="true">{{ $category->icon }}</span> {{ $category->name }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div>
                    <label for="f-subcategory" class="input-label">Subcategoría</label>
                    <input type="text" id="f-subcategory" name="subcategory" value="{{ request('subcategory') }}" placeholder="Opcional" class="input-text">
                </div>

                <fieldset>
                    <legend class="input-label mb-2">Tipo</legend>
                    <div class="flex gap-2">
                        <label class="chip flex-1 justify-center cursor-pointer {{ ! request('type') ? 'chip-active' : '' }}">
                            <input type="radio" name="type" value="" class="sr-only" @checked(! request('type'))> Todos
                        </label>
                        <label class="chip flex-1 justify-center cursor-pointer {{ request('type') == 'producto' ? 'chip-active' : '' }}">
                            <input type="radio" name="type" value="producto" class="sr-only" @checked(request('type') == 'producto')> Productos
                        </label>
                        <label class="chip flex-1 justify-center cursor-pointer {{ request('type') == 'servicio' ? 'chip-active' : '' }}">
                            <input type="radio" name="type" value="servicio" class="sr-only" @checked(request('type') == 'servicio')> Servicios
                        </label>
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="input-label mb-2">Ubicación</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="region" value="{{ request('region') }}" placeholder="Región" class="input-text !py-2 text-sm">
                        <input type="text" name="district" value="{{ request('district') }}" placeholder="Distrito" class="input-text !py-2 text-sm">
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="input-label mb-2">Rango de precio (S/)</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" name="price_min" min="0" value="{{ request('price_min') }}" placeholder="Mín." class="input-text !py-2 text-sm">
                        <input type="number" name="price_max" min="0" value="{{ request('price_max') }}" placeholder="Máx." class="input-text !py-2 text-sm">
                    </div>
                </fieldset>

                <div>
                    <label for="f-sort" class="input-label">Ordenar por</label>
                    <select id="f-sort" name="sort" class="input-text">
                        <option value="" @selected(! request('sort'))>Recientes</option>
                        <option value="precio_asc" @selected(request('sort') == 'precio_asc')>Precio: menor a mayor</option>
                        <option value="precio_desc" @selected(request('sort') == 'precio_desc')>Precio: mayor a menor</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary w-full">Aplicar filtros</button>
            </form>
        </aside>

        {{-- Resultados --}}
        <div class="min-w-0">
            <div class="hidden lg:flex items-center justify-between mb-4">
                <p class="text-sm font-semibold" role="status" aria-live="polite">
                    {{ $publications->total() }} {{ $publications->total() === 1 ? 'publicación encontrada' : 'publicaciones encontradas' }}
                </p>
            </div>

            {{-- Chips de filtros activos --}}
            @if ($activeFilters > 0)
                <div class="flex flex-wrap gap-2 mb-5" aria-label="Filtros aplicados">
                    @if ($currentCategory)
                        <a href="{{ route('public.explore') }}" class="badge badge-neutral">{{ $currentCategory->name }} <x-icon name="close" class="w-3.5 h-3.5" /></a>
                    @endif
                    @foreach (['q' => 'Búsqueda', 'subcategory' => 'Subcategoría', 'type' => 'Tipo', 'region' => 'Región', 'district' => 'Distrito'] as $key => $label)
                        @if (request()->filled($key))
                            <a href="{{ route('public.explore') }}" class="badge badge-neutral">{{ $label }} · {{ request($key) }} <x-icon name="close" class="w-3.5 h-3.5" /></a>
                        @endif
                    @endforeach
                    @if (request()->filled('price_min') || request()->filled('price_max'))
                        <a href="{{ route('public.explore') }}" class="badge badge-neutral">
                            Precio: {{ request()->filled('price_min') ? 'S/ ' . request('price_min') . ' –' : 'hasta' }} {{ request()->filled('price_max') ? 'S/ ' . request('price_max') : 'S/ ∞' }}
                            <x-icon name="close" class="w-3.5 h-3.5" />
                        </a>
                    @endif
                </div>
            @endif

            @if ($publications->isEmpty())
                <div class="empty-state" role="status">
                    <span class="empty-icon"><x-icon name="search" class="w-7 h-7" /></span>
                    <h2 class="mt-4 text-xl font-bold">No encontramos resultados</h2>
                    <p class="text-gray-600 mt-2">Prueba con otros términos o elimina algunos filtros.</p>
                    <a href="{{ route('public.explore') }}" class="btn btn-primary mt-5">Ver todos los emprendimientos</a>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach ($publications as $publication)
                        <x-publication-card :publication="$publication" />
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $publications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
