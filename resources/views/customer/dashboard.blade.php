@extends('layouts.panel-customer')

@section('panel-content')
    <x-panel-header title="Hola, {{ auth()->user()->first_name }}" subtitle="Bienvenido a SONARA. Explora y solicita productos y servicios de emprendedores con discapacidad visual." />

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <x-stat-card label="Solicitudes enviadas" :value="$stats['requests']" icon="clipboard" />
        <x-stat-card label="Pendientes" :value="$stats['pending']" icon="clock" accent="text-amber-700" />
        <x-stat-card label="Completadas" :value="$stats['completed']" icon="check-circle" accent="text-green-700" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <section aria-labelledby="cus-explore" class="card card-body">
            <h2 id="cus-explore" class="section-title !mb-0 mb-4">Descubre emprendimientos</h2>
            <p class="text-sm text-gray-600 mb-4">Productos y servicios hechos con dedicación. Apoya una causa y encuentra lo que buscas.</p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('public.explore') }}" class="btn btn-primary">
                    <x-icon name="search" class="w-4 h-4" /> Explorar el catálogo
                </a>
                <a href="{{ route('public.categories') }}" class="btn btn-secondary">
                    <x-icon name="grid" class="w-4 h-4" /> Ver categorías
                </a>
            </div>
        </section>

        <section aria-labelledby="cus-requests" class="card card-body">
            <div class="flex items-center justify-between mb-4">
                <h2 id="cus-requests" class="section-title !mb-0">Mis últimas solicitudes</h2>
                <a href="{{ route('customer.requests.index') }}" class="link-all">Ver todas <x-icon name="arrow-right" class="w-4 h-4" /></a>
            </div>
            @if ($requests->isEmpty())
                <p class="text-gray-500 text-sm">Aún no has enviado solicitudes. ¡Explora el catálogo y haz tu primer pedido!</p>
            @else
                <ul class="space-y-3">
                    @foreach ($requests as $req)
                        <li class="flex items-center justify-between gap-3 rounded-xl border border-gray-100 p-3 hover:border-purple-200">
                            <div class="min-w-0">
                                <p class="font-medium truncate">{{ $req->publication?->name ?? $req->item_name }}</p>
                                <p class="text-xs text-gray-500">{{ $req->business?->name }} · {{ $req->code }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <x-status-badge :status="$req->status" />
                                <a href="{{ route('customer.requests.show', $req) }}" class="inline-flex items-center gap-1 text-purple-700 text-sm font-semibold">Ver <x-icon name="arrow-right" class="w-3.5 h-3.5" /></a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection