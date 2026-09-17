@extends('layouts.panel-entrepreneur')

@section('panel-content')
    <x-panel-header title="Editar publicación" :subtitle="$publication->name">
        @slot('actions')
            <div class="flex items-center gap-2">
                @if ($publication->status === 'publicada')
                    <a href="{{ route('public.publication', $publication->slug) }}" target="_blank" rel="noopener" class="btn btn-outline">Ver página pública</a>
                @endif
                <a href="{{ route('entrepreneur.flyers.index', $publication) }}" class="btn btn-outline"><x-icon name="sparkles" class="w-5 h-5" /> Generar flyer IA</a>
            </div>
        @endslot
    </x-panel-header>

    <x-status-badge :status="$publication->status" class="mb-6" />

    @if ($publication->status === 'rechazada' && $publication->rejection_reason)
        <div role="alert" class="rounded-xl mb-6 border border-red-200 bg-red-50 p-4 text-red-800">
            <p class="font-semibold">¿Por qué fue rechazada?</p>
            <p class="mt-1 text-sm">{{ $publication->rejection_reason }}</p>
        </div>
    @endif

    <div class="card card-body max-w-2xl">
        @if ($publication->canTransitionTo(\App\Models\Publication::STATUS_PENDIENTE))
            <form method="POST" action="{{ route('entrepreneur.publications.requestApproval', $publication) }}" class="mb-6">
                @csrf
                <button type="submit" class="btn btn-success w-full"><x-icon name="send" class="w-5 h-5" /> Solicitar revisión al administrador</button>
                <p class="text-xs text-gray-500 mt-2">Necesitas un flyer aprobado para enviar tu publicación a revisión.</p>
            </form>
            @if ($errors->has('flyer'))
                <p class="error-message" role="alert">{{ $errors->first('flyer') }}</p>
            @endif
        @endif

        <form method="POST" action="{{ route('entrepreneur.publications.update', $publication) }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label for="business_id" class="input-label">Emprendimiento *</label>
                <select id="business_id" name="business_id" required class="input-text @error('business_id') input-error @enderror">
                    @foreach ($businesses as $biz)
                        <option value="{{ $biz->id }}" @selected(old('business_id', $publication->business_id) == $biz->id)>{{ $biz->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="name" class="input-label">Nombre *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $publication->name) }}" required class="input-text @error('name') input-error @enderror">
                @error('name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="input-label">Descripción</label>
                <textarea id="description" name="description" rows="4" class="input-text">{{ old('description', $publication->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="type" class="input-label">Tipo *</label>
                    <select id="type" name="type" required class="input-text">
                        <option value="producto" @selected(old('type', $publication->type) === 'producto')>Producto</option>
                        <option value="servicio" @selected(old('type', $publication->type) === 'servicio')>Servicio</option>
                    </select>
                </div>
                <div>
                    <label for="currency" class="input-label">Moneda</label>
                    <select id="currency" name="currency" class="input-text">
                        <option value="PEN" @selected(old('currency', $publication->currency) === 'PEN')>S/ (Soles)</option>
                        <option value="USD" @selected(old('currency', $publication->currency) === 'USD')>$ (Dólares)</option>
                    </select>
                </div>
                <div>
                    <label for="price" class="input-label">Precio</label>
                    <input type="number" id="price" name="price" min="0" step="0.01" value="{{ old('price', $publication->price) }}" class="input-text">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="price_min" class="input-label">Precio mínimo</label>
                    <input type="number" id="price_min" name="price_min" min="0" step="0.01" value="{{ old('price_min', $publication->price_min) }}" class="input-text">
                </div>
                <div>
                    <label for="price_max" class="input-label">Precio máximo</label>
                    <input type="number" id="price_max" name="price_max" min="0" step="0.01" value="{{ old('price_max', $publication->price_max) }}" class="input-text">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
@endsection