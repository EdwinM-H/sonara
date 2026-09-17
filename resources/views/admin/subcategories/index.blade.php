@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header title="Subcategorías" subtitle="Afina la búsqueda dentro de cada categoría.">
        @slot('actions')
            <a href="{{ route('admin.subcategories.create') }}" class="btn btn-primary">+ Nueva subcategoría</a>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Categorías</a>
        @endslot
    </x-panel-header>

    <div class="card overflow-hidden">
        <table class="table table-auto">
            <thead>
                <tr>
                    <th scope="col">Nombre</th>
                    <th scope="col">Categoría</th>
                    <th scope="col">Orden</th>
                    <th scope="col">Estado</th>
                    <th scope="col"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subcategories as $sub)
                    <tr>
                        <td class="font-medium">{{ $sub->name }}</td>
                        <td>{{ $sub->category?->name ?? '—' }}</td>
                        <td>{{ $sub->sort_order }}</td>
                        <td>
                            <span class="badge {{ $sub->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">
                                {{ $sub->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('admin.subcategories.edit', $sub) }}" class="btn btn-sm btn-secondary">Editar</a>
                            <form method="POST" action="{{ route('admin.subcategories.destroy', $sub) }}" class="inline" onsubmit="return confirm('¿Eliminar {{ $sub->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection