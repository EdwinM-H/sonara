@php
    $user = auth()->user();
    $navLinks = collect([
        ['label' => 'Inicio', 'route' => 'public.home'],
        ['label' => 'Explorar', 'route' => 'public.explore'],
        ['label' => 'Categorías', 'route' => 'public.categories'],
    ]);
    $unread = $user ? $user->unreadNotifications->count() : 0;
    $notificationsRoute = $user?->isAdmin()
        ? route('admin.notifications.index')
        : ($user?->isEntrepreneur() ? route('entrepreneur.notifications.index') : route('profile.edit'));
    $panelRoute = $user?->isAdmin()
        ? route('admin.dashboard')
        : ($user?->isEntrepreneur() ? route('entrepreneur.dashboard') : ($user?->isCustomer() ? route('customer.dashboard') : null));
@endphp

{{-- Barra de utilidad: accesibilidad siempre visible --}}
<div class="utility-bar hidden sm:block" x-data="{ contrast: false }">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-10 flex items-center justify-between text-xs font-semibold">
        <p class="mono-label !text-white/60">Plataforma accesible · WCAG 2.2 AA</p>
        <div class="flex items-center gap-4">
            <button type="button"
                    @click="contrast = !contrast; document.body.classList.toggle('high-contrast', contrast)"
                    class="inline-flex items-center gap-1.5 hover:text-[var(--color-accent)] transition-colors" :class="contrast ? 'text-[var(--color-accent)]' : 'text-white/75'">
                <x-icon name="eye" class="w-3.5 h-3.5" /> Alto contraste
            </button>
            <button type="button"
                    onclick="window.dispatchEvent(new Event('sonara-read-page'))"
                    class="inline-flex items-center gap-1.5 text-white/75 hover:text-[var(--color-accent)] transition-colors">
                🔊 Leer página
            </button>
        </div>
    </div>
</div>

<header class="site-header" x-data="{ menuOpen: false, userOpen: false }">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center gap-3">
            {{-- Logo --}}
            <a href="{{ route('public.home') }}" class="flex items-center gap-2.5" aria-label="SONARA, ir al inicio">
                <span class="logo-mark text-sm" aria-hidden="true">S</span>
                <span class="text-xl font-display font-black tracking-tight text-gray-900">SONARA</span>
            </a>

            {{-- Enlaces de escritorio --}}
            <nav class="hidden lg:flex items-center gap-1 ml-6" aria-label="Enlaces del portal">
                @foreach ($navLinks as $link)
                    <a href="{{ route($link['route']) }}"
                       class="px-3 py-2 rounded-xl text-sm font-bold {{ request()->routeIs($link['route']) ? 'text-purple-700 bg-purple-50' : 'text-gray-700 hover:text-purple-700 hover:bg-purple-50' }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>

            {{-- Búsqueda escritorio --}}
            <form method="GET" action="{{ route('public.explore') }}" class="hidden md:flex flex-1 max-w-xl mx-auto"
                  role="search" aria-label="Buscar en SONARA">
                <div class="input-group w-full">
                    <span class="input-icon"><x-icon name="search" /></span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar productos, servicios, emprendimientos…"
                           class="input-text" aria-label="Buscar en SONARA">
                </div>
            </form>

            <div class="flex items-center gap-1 ml-auto">
                @if ($user)
                    <a href="{{ $notificationsRoute }}" class="icon-btn relative text-gray-700 hover:text-purple-700" aria-label="Notificaciones{{ $unread > 0 ? ', ' . $unread . ' sin leer' : '' }}">
                        <x-icon name="bell" />
                        @if ($unread > 0)
                            <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-xs rounded-full min-w-[18px] h-[18px] px-1 grid place-items-center font-bold">{{ $unread > 9 ? '9+' : $unread }}</span>
                        @endif
                    </a>
                @else
                    <a href="{{ route('voice-registration.index') }}" class="hidden sm:inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold text-purple-700 hover:bg-purple-50" title="Registro asistido por voz">
                        <x-icon name="mic" class="w-4 h-4" /> Voz
                    </a>
                @endif

                @if ($user)
                    <div class="relative">
                        <button type="button" @click="userOpen = !userOpen" class="flex items-center gap-2 pl-2" aria-haspopup="true" :aria-expanded="userOpen.toString()" aria-label="Menú de {{ $user->name }}">
                            <span class="grid place-items-center w-9 h-9 rounded-full bg-purple-100 text-purple-800 font-bold text-sm ring-2 ring-white">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                            <x-icon name="chevron-down" class="hidden sm:block w-4 h-4 text-gray-500" />
                        </button>
                        <div x-show="userOpen" @click.outside="userOpen = false" x-cloak
                             class="absolute right-0 mt-2 w-60 rounded-xl bg-white text-gray-800 shadow-lg border border-gray-200 z-40 py-1 fade-in">
                            <div class="px-4 py-2 border-b border-gray-100">
                                <p class="text-sm font-bold truncate">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                            </div>
                            @if ($panelRoute)
                                <a href="{{ $panelRoute }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-purple-50">Mi panel</a>
                            @endif
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-purple-50">Mi perfil</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-700 hover:bg-red-50">
                                    <x-icon name="logout" class="w-4 h-4" /> Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex px-3 py-2 rounded-lg text-sm font-semibold text-gray-700 hover:text-purple-700 hover:bg-purple-50">Ingresar</a>
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-purple-700 px-4 py-2 text-sm font-bold text-white hover:bg-purple-800">Registrarse</a>
                @endif

                <button type="button" class="lg:hidden icon-btn text-gray-700" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen.toString()" aria-controls="mobile-menu" aria-label="Abrir menú">
                    <x-icon name="menu" x-show="!menuOpen" />
                    <x-icon name="close" x-show="menuOpen" x-cloak />
                </button>
            </div>
        </div>

        {{-- Búsqueda móvil --}}
        <form method="GET" action="{{ route('public.explore') }}" class="md:hidden pb-3 -mt-1" role="search" aria-label="Buscar en SONARA">
            <div class="input-group">
                <span class="input-icon"><x-icon name="search" /></span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar en SONARA…" class="input-text" aria-label="Buscar en SONARA">
            </div>
        </form>
    </div>

    {{-- Menú móvil --}}
    <div id="mobile-menu" x-show="menuOpen" @click.outside="menuOpen = false" x-cloak class="lg:hidden border-t border-gray-200 bg-white fade-in">
        <nav class="px-4 py-3 space-y-1" aria-label="Menú móvil">
            @foreach ($navLinks as $link)
                <a href="{{ route($link['route']) }}" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold text-gray-800 hover:bg-purple-50">
                    <x-icon name="{{ $link['route'] === 'public.home' ? 'home' : ($link['route'] === 'public.explore' ? 'search' : 'grid') }}" class="w-5 h-5 text-purple-700" />
                    {{ $link['label'] }}
                </a>
            @endforeach
            @if ($user)
                @if ($panelRoute)
                    <a href="{{ $panelRoute }}" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold text-gray-800 hover:bg-purple-50">Mi panel</a>
                @endif
                <a href="{{ $notificationsRoute }}" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold text-gray-800 hover:bg-purple-50">
                    <x-icon name="bell" class="w-5 h-5 text-purple-700" /> Notificaciones
                    @if ($unread > 0)<span class="badge badge-error">{{ $unread }}</span>@endif
                </a>
            @else
                <a href="{{ route('voice-registration.index') }}" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold text-gray-800 hover:bg-purple-50">
                    <x-icon name="mic" class="w-5 h-5 text-purple-700" /> Registro asistido por voz
                </a>
                <a href="{{ route('login') }}" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold text-gray-800 hover:bg-purple-50">
                    <x-icon name="user" class="w-5 h-5 text-purple-700" /> Iniciar sesión
                </a>
            @endif
        </nav>
    </div>
</header>