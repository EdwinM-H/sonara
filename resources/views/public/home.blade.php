@extends('layouts.app')

@section('title', 'Inicio')

@section('hero')
<section class="relative overflow-hidden" style="background-color: var(--color-cream)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-20 lg:py-24">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
            <div class="lg:col-span-7 u-fade-up">
                <p class="mono-label inline-flex items-center gap-2 rounded-full border-[1.5px] px-4 py-2" style="border-color: var(--color-border-2); background: #fff" aria-label="Distintivo">
                    <x-icon name="sparkles" class="w-4 h-4 text-purple-600" /> Plataforma accesible · Voz guiada
                </p>

                <h1 class="mt-6 font-display font-extrabold text-[2.75rem] leading-[0.95] sm:text-6xl sm:leading-[0.94] lg:text-7xl lg:leading-[0.92] tracking-[-0.03em] text-ink">
                    Emprendimientos<br>
                    que se escuchan<br>
                    <span class="text-purple-600">y se apoyan.</span>
                </h1>

                <p class="mt-6 text-lg text-gray-600 max-w-xl leading-relaxed">
                    SONARA conecta a emprendedores con discapacidad visual con clientes que valoran su talento.
                    Explora, solicita y crece en una plataforma diseñada con accesibilidad desde el inicio.
                </p>

                <form action="{{ route('public.explore') }}" method="GET" class="mt-8 max-w-xl" role="search">
                    <label for="hero-search" class="sr-only">Buscar productos o servicios</label>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="input-group flex-1 bg-white rounded-full">
                            <span class="input-icon !text-purple-500"><x-icon name="search" /></span>
                            <input type="search" name="q" id="hero-search" placeholder="Busca productos, servicios o emprendedores…"
                                   class="input-text !rounded-full !bg-white">
                        </div>
                        <button type="submit" class="btn btn-primary !rounded-full">
                            Buscar
                        </button>
                    </div>
                </form>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('voice-registration.index') }}" class="btn btn-secondary">
                        <x-icon name="mic" class="w-5 h-5" /> Registro guiado por voz
                    </a>
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl px-4 py-3 font-bold text-purple-700 hover:text-purple-900">
                        Crear cuenta de emprendedor
                        <x-icon name="arrow-right" class="w-4 h-4" />
                    </a>
                </div>

                <dl class="mt-12 grid grid-cols-3 gap-6 max-w-lg border-t-[1.5px] pt-6" style="border-color: var(--color-border-2)">
                    <div>
                        <dt class="mono-label">Emprendimientos</dt>
                        <dd class="mt-1 text-3xl sm:text-4xl font-display font-extrabold text-ink">{{ $totalBusinesses }}</dd>
                    </div>
                    <div>
                        <dt class="mono-label">Categorías</dt>
                        <dd class="mt-1 text-3xl sm:text-4xl font-display font-extrabold text-ink">{{ $totalCategories }}</dd>
                    </div>
                    <div>
                        <dt class="mono-label">Publicaciones</dt>
                        <dd class="mt-1 text-3xl sm:text-4xl font-display font-extrabold text-ink">{{ $totalPublications }}</dd>
                    </div>
                </dl>
            </div>

            <div class="lg:col-span-5 relative u-fade-up u-delay-2" aria-hidden="true">
                <div class="relative rounded-[2rem] overflow-hidden p-8 sm:p-10" style="background-color: var(--color-primary-deep)">
                    <div class="absolute -top-10 -right-10 w-56 h-56 rounded-full opacity-40 blur-3xl" style="background: var(--color-primary)"></div>
                    <div class="relative grid grid-cols-2 gap-4">
                        <div class="rounded-2xl bg-white/10 border border-white/15 p-5">
                            <span class="grid place-items-center w-10 h-10 rounded-xl mb-3" style="background: var(--color-accent); color: var(--color-ink)"><x-icon name="mic" class="w-5 h-5" /></span>
                            <p class="text-sm text-purple-100">Registro y navegación guiados por comandos de voz.</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 border border-white/15 p-5 mt-8">
                            <span class="grid place-items-center w-10 h-10 rounded-xl mb-3 bg-white/15"><x-icon name="sparkles" class="w-5 h-5 text-white" /></span>
                            <p class="text-sm text-purple-100">Flyers generados con IA para cada publicación.</p>
                        </div>
                    </div>
                    <div class="relative mt-4 rounded-2xl bg-white/10 border border-white/15 p-5 text-sm text-purple-100 leading-relaxed">
                        Navega con comandos de voz, botones grandes y alto contraste.
                        La accesibilidad no es un extra: es el corazón de SONARA.
                    </div>
                </div>

                {{-- Tarjetas flotantes --}}
                <div class="absolute -left-5 top-8 hidden sm:flex items-center gap-2 rounded-2xl bg-white shadow-lift border-[1.5px] border-gray-100 px-4 py-3">
                    <span class="grid place-items-center w-8 h-8 rounded-full bg-green-50 text-green-700"><x-icon name="verified" class="w-4 h-4" /></span>
                    <p class="text-sm font-bold text-ink">Verificado</p>
                </div>
                <div class="absolute -right-4 bottom-10 hidden sm:flex items-center gap-2 rounded-2xl bg-white shadow-lift border-[1.5px] border-gray-100 px-4 py-3">
                    <span class="relative grid place-items-center w-8 h-8 rounded-full" style="background: var(--color-accent)">
                        <x-icon name="mic" class="w-4 h-4 text-ink" />
                    </span>
                    <p class="text-sm font-bold text-ink">Escuchando…</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Marquesina de categorías --}}
<div class="overflow-hidden border-y-[1.5px]" style="background-color: var(--color-ink); border-color: rgba(255,255,255,0.08)" aria-hidden="true">
    <div class="marquee-track py-4">
        @for ($i = 0; $i < 2; $i++)
            @foreach ($categories as $category)
                <span class="mx-5 inline-flex items-center gap-5 text-white/70 font-display font-bold text-lg tracking-tight whitespace-nowrap">
                    {{ strtoupper($category->name) }}
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background: var(--color-accent)"></span>
                </span>
            @endforeach
        @endfor
    </div>
</div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 space-y-20">
    {{-- Categorías --}}
    <section aria-labelledby="home-categorias">
        <div class="section-head">
            <div>
                <p class="mono-label mb-2">02 / Categorías</p>
                <h2 id="home-categorias" class="section-title">Explora por categoría</h2>
            </div>
            <a href="{{ route('public.categories') }}" class="link-all shrink-0">Ver todas <x-icon name="arrow-right" class="w-4 h-4" /></a>
        </div>

        <div class="flex gap-4 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 sm:grid sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 md:overflow-visible">
            @forelse ($categories as $category)
                <a href="{{ route('public.explore').'?category='.$category->id }}"
                   class="card card-body !p-5 text-center shrink-0 w-36 sm:w-auto card-hover u-fade-up">
                    <span class="grid place-items-center w-14 h-14 mx-auto rounded-2xl text-2xl" style="background: var(--color-lavender)" aria-hidden="true">{{ $category->icon }}</span>
                    <p class="mt-3 font-display font-bold text-base">{{ $category->name }}</p>
                    <p class="mono-label mt-1">{{ $category->businesses_count }} {{ $category->businesses_count === 1 ? 'negocio' : 'negocios' }}</p>
                </a>
            @empty
                <p class="text-gray-600">Próximamente más categorías.</p>
            @endforelse
        </div>
    </section>

    {{-- Cómo funciona --}}
    <section aria-labelledby="home-como" class="rounded-[2rem] p-8 sm:p-12" style="background-color: var(--color-lavender)">
        <p class="mono-label text-center mb-2">03 / Cómo funciona</p>
        <h2 id="home-como" class="section-title text-center">Así de fácil funciona SONARA</h2>
        <div class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="text-center">
                <span class="inline-grid place-items-center w-16 h-16 rounded-2xl bg-white shadow-soft text-purple-700 font-display font-extrabold text-xl" aria-hidden="true">01</span>
                <h3 class="mt-4 font-display font-bold text-lg">Crea tu cuenta</h3>
                <p class="mt-1.5 text-sm text-gray-600 text-balance">Escríbenos o usa el registro guiado por voz, sin necesidad de escribir.</p>
            </div>
            <div class="text-center">
                <span class="inline-grid place-items-center w-16 h-16 rounded-2xl bg-white shadow-soft text-purple-700 font-display font-extrabold text-xl" aria-hidden="true">02</span>
                <h3 class="mt-4 font-display font-bold text-lg">Publica o explora</h3>
                <p class="mt-1.5 text-sm text-gray-600 text-balance">Si emprendes, publica con IA generando flyers; si buscas, explora por categorías.</p>
            </div>
            <div class="text-center">
                <span class="inline-grid place-items-center w-16 h-16 rounded-2xl bg-white shadow-soft text-purple-700 font-display font-extrabold text-xl" aria-hidden="true">03</span>
                <h3 class="mt-4 font-display font-bold text-lg">Conecta y solicita</h3>
                <p class="mt-1.5 text-sm text-gray-600 text-balance">Contacta por WhatsApp o teléfono y haz tu solicitud en segundos.</p>
            </div>
        </div>
    </section>

    {{-- Destacados --}}
    <section aria-labelledby="home-destacados">
        <div class="section-head">
            <div>
                <p class="mono-label mb-2">04 / Destacados</p>
                <h2 id="home-destacados" class="section-title">Emprendimientos destacados</h2>
            </div>
            <a href="{{ route('public.explore') }}" class="link-all shrink-0">Ver todos <x-icon name="arrow-right" class="w-4 h-4" /></a>
        </div>

        <div class="flex flex-wrap gap-2 mb-6" role="group" aria-label="Filtrar destacados por tipo">
            <a href="{{ route('public.explore') }}" class="chip chip-active">Todos</a>
            <a href="{{ route('public.explore', ['type' => 'producto']) }}" class="chip">Productos</a>
            <a href="{{ route('public.explore', ['type' => 'servicio']) }}" class="chip">Servicios</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($featured as $publication)
                <x-publication-card :publication="$publication" />
            @empty
                <div class="empty-state col-span-full">
                    <span class="empty-icon"><x-icon name="box" /></span>
                    <p class="mt-3 font-semibold text-gray-800">Aún no hay publicaciones destacadas</p>
                    <p class="text-sm text-gray-600 mt-1">Vuelve pronto, estamos llenando el escaparate.</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Verificados --}}
    @if ($verified->count())
    <section aria-labelledby="home-verificados" class="rounded-[2rem] border-[1.5px] p-6 sm:p-10" style="border-color: var(--color-success-border); background-color: var(--color-success-soft)">
        <div class="section-head">
            <div>
                <p class="mono-label mb-2" style="color: var(--color-success)">05 / Confianza verificada</p>
                <h2 id="home-verificados" class="section-title flex items-center gap-3">
                    <span class="grid place-items-center w-10 h-10 rounded-xl bg-green-600 text-white" aria-hidden="true">
                        <x-icon name="verified" class="w-5 h-5" />
                    </span>
                    Emprendedores verificados
                </h2>
            </div>
            <a href="{{ route('public.explore').'?verificado=1' }}" class="link-all shrink-0" style="color: var(--color-success)">Ver todos <x-icon name="arrow-right" class="w-4 h-4" /></a>
        </div>
        <p class="-mt-3 mb-6 text-sm text-gray-600">Emprendimientos que han acreditado identidad y calidad.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($verified as $publication)
                <x-publication-card :publication="$publication" />
            @endforeach
        </div>
    </section>
    @endif

    {{-- Llamada a la acción --}}
    <section aria-labelledby="home-cta" class="relative overflow-hidden rounded-[2rem] text-white" style="background-color: var(--color-primary-deep)">
        <div class="absolute -top-16 -right-16 w-72 h-72 rounded-full opacity-30 blur-3xl" style="background: var(--color-primary)" aria-hidden="true"></div>
        <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-10 p-8 sm:p-12">
            <div>
                <p class="mono-label !text-purple-200 mb-3">06 / Empieza hoy</p>
                <h2 id="home-cta" class="text-3xl sm:text-4xl font-display font-extrabold leading-[0.98] text-balance">¿Eres emprendedor con discapacidad visual?</h2>
                <p class="mt-4 text-purple-100 max-w-md leading-relaxed">
                    Regístrate con nuestro asistente por voz, crea tu emprendimiento y genera flyers con IA para llegar a más clientes.
                </p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="{{ route('voice-registration.index') }}" class="btn btn-dark">
                        <x-icon name="mic" class="w-5 h-5" /> Registro guiado por voz
                    </a>
                    <a href="{{ route('register') }}" class="btn btn-secondary !bg-transparent !text-white !border-white/40 hover:!bg-white/10">
                        Registro tradicional
                    </a>
                </div>
            </div>

            {{-- Transcripción de ejemplo --}}
            <div class="rounded-2xl bg-white/[0.06] border border-white/15 p-5 font-mono text-sm leading-relaxed" aria-hidden="true">
                <p class="mono-label !text-purple-300 mb-3">Transcripción de ejemplo</p>
                <p class="text-purple-100">
                    <span class="text-[var(--color-accent)]">SONARA:</span> ¿Cuál es el nombre de tu emprendimiento?
                </p>
                <p class="mt-2 text-white">
                    <span class="text-purple-300">TÚ:</span> Manos Creativas, hago artesanías en telar.<span class="caret-blink h-4 align-middle ml-0.5"></span>
                </p>
                <p class="mt-2 text-purple-100">
                    <span class="text-[var(--color-accent)]">SONARA:</span> Perfecto, registrado. ¿En qué categoría lo ubicamos?
                </p>
            </div>
        </div>
    </section>
</div>
@endsection
