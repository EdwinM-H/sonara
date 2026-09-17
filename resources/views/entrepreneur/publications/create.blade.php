@extends('layouts.panel-entrepreneur')

@section('panel-content')
    <x-panel-header title="Nueva publicación" subtitle="Define el producto o servicio que quieres ofrecer." />

    <div class="card card-body max-w-2xl">
        <form method="POST" action="{{ route('entrepreneur.publications.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="business_id" class="input-label">Emprendimiento *</label>
                <select id="business_id" name="business_id" required class="input-text @error('business_id') input-error @enderror">
                    <option value="">Selecciona un emprendimiento</option>
                    @foreach ($businesses as $biz)
                        <option value="{{ $biz->id }}" @selected(old('business_id') == $biz->id)>{{ $biz->name }}</option>
                    @endforeach
                </select>
                @error('business_id')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="name" class="input-label">Nombre *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required class="input-text @error('name') input-error @enderror">
                @error('name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="input-label">Descripción</label>
                <textarea id="description" name="description" rows="4" class="input-text">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="type" class="input-label">Tipo *</label>
                    <select id="type" name="type" required class="input-text">
                        <option value="producto" @selected(old('type') === 'producto')>Producto</option>
                        <option value="servicio" @selected(old('type') === 'servicio')>Servicio</option>
                    </select>
                </div>
                <div>
                    <label for="currency" class="input-label">Moneda</label>
                    <select id="currency" name="currency" class="input-text">
                        <option value="PEN" @selected(old('currency') === 'PEN')>S/ (Soles)</option>
                        <option value="USD" @selected(old('currency') === 'USD')>$ (Dólares)</option>
                    </select>
                </div>
                <div>
                    <label for="price" class="input-label">Precio (S/ o $)</label>
                    <input type="number" id="price" name="price" min="0" step="0.01" value="{{ old('price') }}" class="input-text">
                    @error('price')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="price_min" class="input-label">Precio mínimo</label>
                    <input type="number" id="price_min" name="price_min" min="0" step="0.01" value="{{ old('price_min') }}" class="input-text">
                </div>
                <div>
                    <label for="price_max" class="input-label">Precio máximo</label>
                    <input type="number" id="price_max" name="price_max" min="0" step="0.01" value="{{ old('price_max') }}" class="input-text">
                    @error('price_max')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Crear publicación</button>
        </form>
    </div>
@endsection