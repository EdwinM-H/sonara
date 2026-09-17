@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header title="Emprendimientos" subtitle="Todos los emprendimientos del portal." />

    <div class="card overflow-hidden">
        <table class="table table-auto">
            <thead>
                <tr>
                    <th scope="col">Nombre</th>
                    <th scope="col">Emprendedor</th>
                    <th scope="col">Categoría</th>
                    <th scope="col">Publicaciones</th>
                    <th scope="col">Estado</th>
                    <th scope="col"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($businesses as $biz)
                    <tr>
                        <td class="font-medium">{{ $biz->name }}</td>
                        <td>{{ $biz->entrepreneurProfile?->user?->name ?? '—' }}</td>
                        <td>{{ $biz->category?->name ?? '—' }}</td>
                        <td>{{ $biz->publications_count }}</td>
                        <td><x-status-badge :status="$biz->status" /></td>
                        <td><a href="{{ route('admin.businesses.show', $biz) }}" class="btn btn-sm btn-outline">Ver</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $businesses->links() }}</div>
@endsection