@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header title="Catálogos" subtitle="Opciones que el emprendedor escucha y elige al registrar su emprendimiento por voz." />

    <div class="card overflow-x-auto mb-8">
        <table class="table table-auto">
            <thead>
                <tr>
                    <th scope="col">Catálogo</th>
                    <th scope="col">Descripción</th>
                    <th scope="col">Opciones</th>
                    <th scope="col"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="font-medium">Categorías</td>
                    <td>Rubro del emprendimiento (activas).</td>
                    <td>{{ $categoriesCount }}</td>
                    <td class="whitespace-nowrap">
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-sm btn-secondary">Gestionar</a>
                    </td>
                </tr>
                @foreach ($types as $type)
                    <tr>
                        <td class="font-medium">{{ $type->name }}</td>
                        <td>{{ $type->description ?: '—' }}</td>
                        <td>{{ $type->options_count }}</td>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('admin.catalogs.show', $type) }}" class="btn btn-sm btn-secondary">Gestionar</a>
                            @unless ($type->is_system)
                                <form method="POST" action="{{ route('admin.catalogs.destroy', $type) }}" class="inline"
                                      onsubmit="return confirm('¿Eliminar el catálogo {{ $type->name }} y todas sus opciones?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="card card-body max-w-xl">
        <h2 class="font-display font-bold text-lg mb-4">Nuevo tipo de catálogo</h2>
        <form method="POST" action="{{ route('admin.catalogs.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="name" class="input-label">Nombre *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="100" class="input-text @error('name') input-error @enderror">
                @error('name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="description" class="input-label">Descripción</label>
                <input type="text" id="description" name="description" value="{{ old('description') }}" maxlength="255" class="input-text">
            </div>
            <button type="submit" class="btn btn-primary">Crear catálogo</button>
        </form>
    </div>
@endsection
