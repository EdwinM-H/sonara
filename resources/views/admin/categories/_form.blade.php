@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header title="@isset($category) Editar categoría @else Nueva categoría @endisset" />

    <div class="card card-body max-w-xl">
        <form method="POST"
              action="{{ isset($category) ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
              class="space-y-4">
            @csrf
            @if (isset($category)) @method('PATCH') @endif

            <div>
                <label for="name" class="input-label">Nombre *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $category->name ?? '') }}" required class="input-text @error('name') input-error @enderror">
                @error('name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="icon" class="input-label">Icono (emoji)</label>
                    <input type="text" id="icon" name="icon" value="{{ old('icon', $category->icon ?? '') }}" maxlength="50" class="input-text">
                </div>
                <div>
                    <label for="sort_order" class="input-label">Orden</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="input-text">
                </div>
            </div>

            <div>
                <label for="description" class="input-label">Descripción</label>
                <textarea id="description" name="description" rows="3" class="input-text">{{ old('description', $category->description ?? '') }}</textarea>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" @checked($category->is_active ?? true) class="rounded border-gray-300 text-purple-700">
                Categoría activa (visible en el portal)
            </label>

            <div class="flex gap-3">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection