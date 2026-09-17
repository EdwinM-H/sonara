@extends('layouts.panel-entrepreneur')

@section('panel-content')
    <x-panel-header title="Mis emprendimientos" subtitle="Administra la tienda que presentas al público.">
        @slot('actions')
            <a href="{{ route('entrepreneur.businesses.create') }}" class="btn btn-primary">+ Nuevo emprendimiento</a>
        @endslot
    </x-panel-header>

    @if ($businesses->isEmpty())
        <div class="card card-body text-center py-10">
            <p class="text-4xl mb-3 grid place-items-center w-14 h-14 rounded-2xl bg-purple-50 text-purple-600 mx-auto"><x-icon name="tag" /></p>
            <p class="font-semibold">Aún no tienes emprendimientos</p>
            <p class="text-gray-500 text-sm mt-1">Crea tu primer emprendimiento para empezar a publicar productos y servicios.</p>
        </div>
    @else
        <div class="card overflow-hidden">
            <table class="table table-auto">
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Categoría</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Publicaciones</th>
                        <th scope="col">Estado</th>
                        <th scope="col"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($businesses as $biz)
                        <tr>
                            <td class="font-medium">{{ $biz->name }}</td>
                            <td>{{ $biz->category?->name ?? '—' }}</td>
                            <td>{{ ucfirst($biz->type) }}</td>
                            <td>{{ $biz->publications_count }}</td>
                            <td>
                                <x-status-badge :status="$biz->status" />
                            </td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('public.business', $biz->slug) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline">Ver</a>
                                <a href="{{ route('entrepreneur.businesses.edit', $biz) }}" class="btn btn-sm btn-secondary">Editar</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection