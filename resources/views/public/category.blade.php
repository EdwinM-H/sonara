@extends('layouts.app')

@section('title', $category->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav aria-label="Migas de pan" class="text-sm text-gray-500 mb-6">
        <ol class="flex flex-wrap items-center gap-1" vocab="https://schema.org/" typeof="BreadcrumbList">
            <li property="itemListElement" typeof="ListItem">
                <a property="item" typeof="WebPage" href="{{ route('public.home') }}" class="hover:text-purple-700"><span property="name">Inicio</span></a>
            </li>
            <li aria-hidden="true"><x-icon name="chevron-right" class="w-4 h-4" /></li>
            <li property="itemListElement" typeof="ListItem">
                <a property="item" typeof="WebPage" href="{{ route('public.categories') }}" class="hover:text-purple-700"><span property="name">Categorías</span></a>
            </li>
            <li aria-hidden="true"><x-icon name="chevron-right" class="w-4 h-4" /></li>
            <li property="itemListElement" typeof="ListItem" aria-current="page">
                <span property="name" class="text-gray-800 font-medium">{{ $category->name }}</span>
            </li>
        </ol>
    </nav>

    <header class="rounded-[2rem] p-6 sm:p-8 mb-10 flex items-center gap-5" style="background-color: var(--color-cream)">
        <span class="grid place-items-center w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-white shadow-soft text-3xl sm:text-4xl shrink-0" aria-hidden="true">{{ $category->icon }}</span>
        <div>
            <p class="mono-label mb-1">Categoría</p>
            <h1 class="section-title">{{ $category->name }}</h1>
            <p class="text-gray-600 mt-1">{{ $category->description }}</p>
        </div>
    </header>

    @if ($publications->isEmpty())
        <div class="empty-state" role="status">
            <span class="empty-icon"><x-icon name="box" /></span>
            <p class="mt-3 text-lg font-semibold text-gray-800">Aún no hay publicaciones en esta categoría</p>
            <p class="text-sm text-gray-600 mt-1">Prueba a explorar otras categorías mientras tanto.</p>
            <a href="{{ route('public.explore') }}" class="btn btn-outline mt-5">Explorar todo</a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach ($publications as $publication)
                <x-publication-card :publication="$publication" />
            @endforeach
        </div>
        <div class="mt-8">{{ $publications->links() }}</div>
    @endif
</div>
@endsection