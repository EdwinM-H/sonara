@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header title="Categorías" subtitle="Organiza el catálogo público por categorías.">
        @slot('actions')
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">+ Nueva categoría</a>
            <a href="{{ route('admin.subcategories.index') }}" class="btn btn-secondary">Subcategorías</a>
        @endslot
    </x-panel-header>

    <div class="card overflow-hidden">
        <table class="table table-auto">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Nombre</th>
                    <th scope="col">Icono</th>
                    <th scope="col">Subcategorías</th>
                    <th scope="col">Estado</th>
                    <th scope="col"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($categories as $cat)
                    <tr>
                        <td>{{ $cat->sort_order }}</td>
                        <td class="font-medium">{{ $cat->name }}</td>
                        <td aria-hidden="true">{{ $cat->icon }}</td>
                        <td>{{ $cat->subcategories_count }}</td>
                        <td>
                            <span class="badge {{ $cat->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">
                                {{ $cat->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('admin.categories.edit', $cat) }}" class="btn btn-sm btn-secondary">Editar</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" class="inline" onsubmit="return confirm('¿Eliminar la categoría {{ $cat->name }}?');">
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