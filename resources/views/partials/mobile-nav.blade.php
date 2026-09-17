@php
    $user = auth()->user();
    $panelRoute = $user?->isAdmin()
        ? route('admin.dashboard')
        : ($user?->isEntrepreneur() ? route('entrepreneur.dashboard') : ($user?->isCustomer() ? route('customer.dashboard') : null));
    $notifRoute = $user?->isAdmin()
        ? route('admin.notifications.index')
        : ($user?->isEntrepreneur() ? route('entrepreneur.notifications.index') : route('profile.edit'));
    $items = collect([
        ['label' => 'Inicio', 'icon' => 'home', 'href' => route('public.home'), 'routeIs' => 'public.home'],
        ['label' => 'Explorar', 'icon' => 'search', 'href' => route('public.explore'), 'routeIs' => 'public.explore'],
        ['label' => 'Categorías', 'icon' => 'grid', 'href' => route('public.categories'), 'routeIs' => 'public.categories'],
    ]);
    if ($user) {
        $items->push(['label' => 'Notif.', 'icon' => 'bell', 'href' => $notifRoute, 'routeIs' => ['*.notifications.index'], 'badge' => $user->unreadNotifications->count()]);
        if ($panelRoute) {
            $items->push(['label' => 'Panel', 'icon' => 'chart', 'href' => $panelRoute, 'routeIs' => ['*.dashboard']]);
        }
    } else {
        $items->push(['label' => 'Voz', 'icon' => 'mic', 'href' => route('voice-registration.index'), 'routeIs' => 'voice-registration.index']);
        $items->push(['label' => 'Acceso', 'icon' => 'user', 'href' => route('login'), 'routeIs' => 'login']);
    }
@endphp

<nav class="nav-bottom lg:hidden" aria-label="Navegación rápida">
    @foreach ($items as $item)
        <a href="{{ $item['href'] }}"
           aria-current="{{ request()->routeIs($item['routeIs']) ? 'page' : false }}">
            <span class="relative">
                <x-icon name="{{ $item['icon'] }}" class="w-6 h-6" />
                @if (! empty($item['badge']))
                    <span class="absolute -top-1 -right-2 bg-red-500 text-white text-[10px] rounded-full min-w-[16px] h-[16px] px-1 grid place-items-center font-bold">{{ $item['badge'] > 9 ? '9+' : $item['badge'] }}</span>
                @endif
            </span>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>