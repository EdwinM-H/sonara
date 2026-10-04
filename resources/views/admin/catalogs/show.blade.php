@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header :title="'Catálogo · '.$type->name" :subtitle="$type->description">
        @slot('actions')
            <a href="{{ route('admin.catalogs.index') }}" class="btn btn-secondary">Volver a catálogos</a>
        @endslot
    </x-panel-header>

    <div class="card card-body mb-8">
        <h2 class="font-display font-bold text-lg mb-4">Nueva opción</h2>
        <form method="POST" action="{{ route('admin.catalogs.options.store', $type) }}" class="grid grid-cols-1 sm:grid-cols-[1fr_8rem_auto] gap-3 items-end">
            @csrf
            <div>
                <label for="new-name" class="input-label">Nombre *</label>
                <input type="text" id="new-name" name="name" value="{{ old('name') }}" required maxlength="150" class="input-text @error('name') input-error @enderror">
            </div>
            <div>
                <label for="new-sort" class="input-label">Orden</label>
                <input type="number" id="new-sort" name="sort_order" min="0" value="{{ old('sort_order', 0) }}" class="input-text">
            </div>
            <button type="submit" class="btn btn-primary">Agregar</button>
        </form>
        @error('name')<p class="error-message mt-2" role="alert">{{ $message }}</p>@enderror
    </div>

    <div class="card overflow-hidden">
        <table class="table table-auto">
            <thead>
                <tr>
                    <th scope="col">Nombre</th>
                    <th scope="col">Se reconoce por voz como</th>
                    <th scope="col">Orden</th>
                    <th scope="col"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($type->options as $option)
                    <tr>
                        <td>
                            <label for="name-{{ $option->id }}" class="sr-only">Nombre</label>
                            <input type="text" id="name-{{ $option->id }}" name="name" form="update-{{ $option->id }}"
                                   value="{{ $option->name }}" required maxlength="150" class="input-text">
                        </td>
                        <td class="font-mono text-sm">{{ $option->normalized_name }}</td>
                        <td class="w-28">
                            <label for="sort-{{ $option->id }}" class="sr-only">Orden</label>
                            <input type="number" id="sort-{{ $option->id }}" name="sort_order" form="update-{{ $option->id }}"
                                   min="0" value="{{ $option->sort_order }}" class="input-text">
                        </td>
                        <td class="whitespace-nowrap">
                            <form id="update-{{ $option->id }}" method="POST" action="{{ route('admin.catalogs.options.update', [$type, $option]) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-secondary">Guardar</button>
                            </form>
                            <form method="POST" action="{{ route('admin.catalogs.options.destroy', [$type, $option]) }}" class="inline"
                                  onsubmit="return confirm('¿Eliminar la opción {{ $option->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-gray-500 py-8">
                            Este catálogo no tiene opciones. Mientras esté vacío, el emprendedor podrá dictar un valor libre y se te notificará.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
