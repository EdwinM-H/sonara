@extends('layouts.app', ['bodyClasses' => '!bg-white'])

@section('title', 'Inicio')

@section('content')
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <h1 class="sr-only">SONARA: emprendimientos de personas con discapacidad visual</h1>

    {{-- 1. Búsqueda --}}
    <section aria-label="Buscar" class="pt-6 sm:pt-10">
        <form action="{{ route('public.explore') }}" method="GET" role="search" class="mx-auto max-w-3xl">
            <label for="home-search" class="sr-only">Buscar productos, servicios o emprendedores</label>
            <div class="flex items-stretch overflow-hidden rounded-full border-2 border-purple-600 bg-white shadow-sm focus-within:ring-4 focus-within:ring-purple-200">
                <span class="hidden items-center pl-5 text-gray-600 sm:flex" aria-hidden="true"><x-icon name="search" class="h-5 w-5" /></span>
                <input type="search" id="home-search" name="q" value="{{ request('q') }}"
                       placeholder="Busca productos, servicios o emprendedores…"
                       class="min-w-0 flex-1 border-0 bg-transparent px-4 py-3.5 text-base focus:ring-0 sm:px-3 sm:text-lg">
                <button type="submit" class="m-1 inline-flex items-center gap-2 rounded-full bg-purple-600 px-5 font-bold text-white hover:bg-purple-700 sm:px-7">
                    <x-icon name="search" class="h-5 w-5 sm:hidden" />
                    <span class="sr-only sm:not-sr-only">Buscar</span>
                </button>
            </div>
        </form>
    </section>

    {{-- 2. Categorías (las del admin) --}}
    @if ($categories->isNotEmpty())
        <section aria-labelledby="home-categorias" class="pt-8">
            <div class="mb-3 flex items-end justify-between gap-4">
                <h2 id="home-categorias" class="text-xl font-extrabold sm:text-2xl">Categorías</h2>
                <a href="{{ route('public.categories') }}" class="shrink-0 text-sm font-bold text-purple-700 hover:underline">Ver todas</a>
            </div>
            <ul class="-mx-4 flex snap-x gap-3 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0 [scrollbar-width:thin]">
                @foreach ($categories as $category)
                    <li class="shrink-0 snap-start">
                        <a href="{{ route('public.category', $category) }}"
                           class="flex w-24 flex-col items-center gap-2 rounded-2xl p-2 text-center hover:bg-purple-100 sm:w-28">
                            <span class="grid h-16 w-16 place-items-center rounded-full bg-purple-100 text-3xl ring-1 ring-purple-400 sm:h-20 sm:w-20" aria-hidden="true">{{ $category->icon ?: '🛍️' }}</span>
                            <span class="line-clamp-2 text-xs font-semibold leading-tight text-purple-700 sm:text-sm">{{ $category->name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- 3. Destacados --}}
    <section aria-labelledby="home-destacados" class="pt-10">
        <div class="mb-4 flex items-end justify-between gap-4">
            <h2 id="home-destacados" class="text-xl font-extrabold sm:text-2xl">
                <span class="mr-1 rounded-md bg-purple-600 px-2 py-0.5 text-white">Destacados</span> recientes
            </h2>
            <a href="{{ route('public.explore') }}" class="shrink-0 text-sm font-bold text-purple-700 hover:underline">Ver todos</a>
        </div>

        @if ($featured->isEmpty())
            <div class="rounded-2xl border border-dashed border-gray-300 p-10 text-center text-gray-600">
                Aún no hay emprendimientos publicados. ¡Vuelve pronto!
            </div>
        @else
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($featured as $publication)
                    <x-product-card :publication="$publication" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- 4. Por sector (los del admin; sin anuncios no se muestran) --}}
    @foreach ($sectors as $sector)
        <section aria-labelledby="sector-{{ $loop->index }}" class="pt-10"
                 x-data="{ scroll(dir) { $refs.track.scrollBy({ left: dir * $refs.track.clientWidth * 0.9, behavior: 'smooth' }) } }">
            <div class="mb-4 flex items-end justify-between gap-4">
                <h2 id="sector-{{ $loop->index }}" class="text-xl font-extrabold sm:text-2xl">
                    Sector <span class="text-purple-700">{{ $sector['name'] }}</span>
                </h2>
                <div class="hidden gap-2 sm:flex">
                    <button type="button" @click="scroll(-1)" class="grid h-9 w-9 place-items-center rounded-full border border-gray-300 hover:bg-gray-100" aria-label="Anteriores de {{ $sector['name'] }}">
                        <x-icon name="chevron-right" class="h-4 w-4 rotate-180" />
                    </button>
                    <button type="button" @click="scroll(1)" class="grid h-9 w-9 place-items-center rounded-full border border-gray-300 hover:bg-gray-100" aria-label="Siguientes de {{ $sector['name'] }}">
                        <x-icon name="chevron-right" class="h-4 w-4" />
                    </button>
                </div>
            </div>
            <ul x-ref="track" class="-mx-4 flex snap-x snap-mandatory gap-3 overflow-x-auto px-4 pb-2 sm:mx-0 sm:gap-4 sm:px-0 [scrollbar-width:thin]">
                @foreach ($sector['publications'] as $publication)
                    <li class="w-[70%] shrink-0 snap-start sm:w-[calc((100%-1rem)/2)] lg:w-[calc((100%-2rem)/3)] xl:w-[calc((100%-3rem)/4)]">
                        <x-product-card :publication="$publication" />
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach

    {{-- 5. Banner de la plataforma --}}
    <section aria-labelledby="home-banner" class="mt-14 overflow-hidden rounded-3xl bg-gradient-to-br from-purple-700 to-purple-900 text-white">
        <div class="grid grid-cols-1 gap-8 p-6 sm:p-10 lg:grid-cols-5 lg:items-center">
            <div class="lg:col-span-3">
                <p class="text-sm font-bold uppercase tracking-wider text-purple-100">Plataforma accesible · Voz guiada</p>
                <h2 id="home-banner" class="mt-3 text-3xl font-extrabold leading-tight sm:text-4xl">
                    Emprendimientos que se escuchan y se apoyan.
                </h2>
                <p class="mt-4 max-w-xl text-base leading-relaxed text-purple-100 sm:text-lg">
                    SONARA conecta a emprendedores con discapacidad visual con clientes que valoran su talento.
                    Hoy hay {{ $totalBusinesses }} {{ $totalBusinesses === 1 ? 'emprendimiento' : 'emprendimientos' }} esperándote.
                </p>
            </div>
            <div class="flex flex-col gap-3 lg:col-span-2">
                <a href="{{ route('voice-registration.index') }}" class="inline-flex min-h-[3rem] items-center justify-center gap-2 rounded-full bg-white px-6 font-bold text-purple-800 hover:bg-purple-100">
                    <x-icon name="mic" class="h-5 w-5" /> Soy emprendedor: registro por voz
                </a>
                <a href="{{ route('register') }}" class="inline-flex min-h-[3rem] items-center justify-center rounded-full border-2 border-white/70 px-6 font-bold text-white hover:bg-white/10">
                    Crear cuenta para comprar
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
