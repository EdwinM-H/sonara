@extends('layouts.app')

@section('content')
@php $user = auth()->user(); @endphp
<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    <div class="lg:grid lg:grid-cols-[280px_1fr] lg:gap-8 lg:items-start">

        {{-- Navegación móvil (chips deslizables) --}}
        <nav class="lg:hidden -mx-4 px-4 mb-6 flex gap-2 overflow-x-auto" aria-label="Menú de {{ $panel }}">
            @foreach ($sidebarItems as $item)
                @php $isActive = request()->routeIs($item['active'] ?? null) || request()->url() === $item['url']; @endphp
                <a href="{{ $item['url'] }}" @if ($isActive) aria-current="page" @endif
                   class="chip whitespace-nowrap {{ $isActive ? 'chip-active' : '' }}">
                    <x-icon name="{{ $item['icon'] }}" class="w-4 h-4" />
                    {{ $item['label'] }}
                </a>
            @endforeach
            <a href="{{ route('public.home') }}" class="chip whitespace-nowrap">
                <x-icon name="home" class="w-4 h-4" /> Ver sitio
            </a>
        </nav>

        {{-- Sidebar escritorio --}}
        <aside class="hidden lg:block lg:sticky lg:top-24" aria-label="Menú de {{ $panel }}">
            <div class="rounded-[1.5rem] !p-3 text-white relative overflow-hidden" style="background-color: var(--color-primary-deep)">
                <div class="absolute -top-16 -right-16 w-48 h-48 rounded-full opacity-25 blur-3xl pointer-events-none" style="background: var(--color-primary)"></div>

                <div class="relative flex items-center gap-3 px-2 py-3 mb-2 border-b border-white/10">
                    <span class="grid place-items-center w-11 h-11 rounded-full font-bold shrink-0" style="background: var(--color-accent); color: var(--color-ink)">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    <div class="min-w-0">
                        <p class="font-display font-bold text-sm truncate">{{ $user->name }}</p>
                        <p class="mono-label !text-purple-300 truncate">{{ $panel }}</p>
                    </div>
                </div>

                <nav class="relative space-y-0.5 max-h-[calc(100vh-18rem)] overflow-y-auto pr-0.5 py-1">
                    @foreach ($sidebarItems as $item)
                        @php $isActive = request()->routeIs($item['active'] ?? null) || request()->url() === $item['url']; @endphp
                        <a href="{{ $item['url'] }}" @if ($isActive) aria-current="page" @endif
                           class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-bold transition-colors duration-150 {{ $isActive ? 'text-[var(--color-ink)]' : 'text-purple-100 hover:bg-white/10' }}"
                           @if ($isActive) style="background-color: var(--color-accent)" @endif>
                            <x-icon name="{{ $item['icon'] }}" class="{{ $isActive ? '' : 'text-purple-300' }} w-5 h-5 shrink-0" />
                            <span class="truncate">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="relative mt-2 pt-2 border-t border-white/10">
                    <a href="{{ route('public.home') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-bold text-purple-100 hover:bg-white/10">
                        <x-icon name="home" class="w-5 h-5 text-purple-300 shrink-0" /> Ver sitio
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-bold text-red-300 hover:bg-white/10">
                            <x-icon name="logout" class="w-5 h-5 shrink-0" /> Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Contenido --}}
        <section class="min-w-0 mt-6 lg:mt-0">
            @yield('panel-content')
        </section>
    </div>
</div>
@endsection
