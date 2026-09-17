@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <header class="mb-10">
        <p class="mono-label mb-2">Marketplace</p>
        <h1 class="section-title">Categorías</h1>
        <p class="text-gray-600 mt-2 text-lg">Explora los emprendimientos por su categoría.</p>
    </header>

    @forelse ($categories as $category)
        <section aria-labelledby="cat-{{ $category->id }}" class="card card-body !p-5 sm:!p-6 mb-5 card-hover">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <h2 id="cat-{{ $category->id }}" class="text-lg font-display font-bold flex items-center gap-3">
                    <span class="grid place-items-center w-12 h-12 rounded-2xl text-xl" style="background: var(--color-lavender)" aria-hidden="true">{{ $category->icon }}</span>
                    {{ $category->name }}
                    <span class="badge badge-neutral">{{ $category->businesses_count }}</span>
                </h2>
                <a href="{{ route('public.explore').'?category='.$category->id }}" class="link-all">
                    Ver todo <x-icon name="arrow-right" class="w-4 h-4" />
                </a>
            </div>

            @if ($category->subcategories->isNotEmpty())
                <ul class="mt-4 flex flex-wrap gap-2">
                    @foreach ($category->subcategories as $sub)
                        <li>
                            <a href="{{ route('public.explore').'?category='.$category->id.'&subcategory='.$sub->name }}"
                               class="chip">
                                {{ $sub->name }}
                                <x-icon name="chevron-right" class="w-3 h-3 text-gray-400" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @empty
        <div class="empty-state">
            <span class="empty-icon"><x-icon name="grid" /></span>
            <p class="mt-3 font-semibold text-gray-800">Aún no hay categorías</p>
            <p class="text-sm text-gray-600 mt-1">Pronto abriremos nuevas categorías.</p>
        </div>
    @endforelse
</div>
@endsection