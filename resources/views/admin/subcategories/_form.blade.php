@props(['subcategory' => null, 'categories'])

<form method="POST"
      action="{{ $subcategory ? route('admin.subcategories.update', $subcategory) : route('admin.subcategories.store') }}"
      class="space-y-4">
    @csrf
    @if ($subcategory) @method('PATCH') @endif

    <div>
        <label for="category_id" class="input-label">Categoría *</label>
        <select id="category_id" name="category_id" required class="input-text">
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected(old('category_id', $subcategory->category_id ?? null) == $cat->id)>{{ $cat->icon }} {{ $cat->name }}</option>
            @endforeach
        </select>
        @error('category_id')<p class="error-message" role="alert">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="name" class="input-label">Nombre *</label>
        <input type="text" id="name" name="name" value="{{ old('name', $subcategory->name ?? '') }}" required class="input-text @error('name') input-error @enderror">
        @error('name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="sort_order" class="input-label">Orden</label>
        <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $subcategory->sort_order ?? 0) }}" class="input-text">
    </div>

    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input type="checkbox" name="is_active" value="1" @checked($subcategory->is_active ?? true) class="rounded border-gray-300 text-purple-700">
        Subcategoría activa
    </label>

    <div class="flex gap-3">
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('admin.subcategories.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>