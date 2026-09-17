@props(['business' => null, 'categories', 'days', 'subcategories' => collect()])

@php
    $b = $business;
    $categoryJson = $categories->loadMissing('subcategories')->map(fn ($c) => [
        'id' => $c->id,
        'name' => $c->name,
        'subs' => $c->subcategories->where('is_active', true)->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
    ])->values()->toJson();
@endphp

<div x-data="{ category_id: '{{ old('category_id', $b?->category_id) }}', categoryJson: {{ $categoryJson }}, subs: () => (categoryJson.find(c => String(c.id) === String(category_id))?.subs ?? []).map(s => ({id: String(s.id), name: s.name})) }"
     class="space-y-4">
    <div>
        <label for="name" class="input-label">Nombre del emprendimiento *</label>
        <input type="text" id="name" name="name" value="{{ old('name', $b?->name) }}" required class="input-text @error('name') input-error @enderror">
        @error('name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="description" class="input-label">Descripción</label>
        <textarea id="description" name="description" rows="4" class="input-text">{{ old('description', $b?->description) }}</textarea>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label for="category_id" class="input-label">Categoría</label>
            <select id="category_id" name="category_id" x-model="category_id" class="input-text @error('category_id') input-error @enderror">
                <option value="">Sin categoría</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                @endforeach
            </select>
            @error('category_id')<p class="error-message" role="alert">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="subcategory_id" class="input-label">Subcategoría</label>
            <select id="subcategory_id" name="subcategory_id" class="input-text">
                <option value="">Sin subcategoría</option>
                <template x-for="s in subs()" :key="s.id">
                    <option :value="s.id" x-text="s.name" :selected="String('{{ old('subcategory_id', $b?->subcategory_id) }}') === s.id"></option>
                </template>
            </select>
            @error('subcategory_id')<p class="error-message" role="alert">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="type" class="input-label">Tipo *</label>
            <select id="type" name="type" class="input-text @error('type') input-error @enderror" required>
                <option value="producto" @selected(old('type', $b?->type) === 'producto')>Producto</option>
                <option value="servicio" @selected(old('type', $b?->type) === 'servicio')>Servicio</option>
            </select>
            @error('type')<p class="error-message" role="alert">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label for="availability" class="input-label">Disponibilidad *</label>
            <select id="availability" name="availability" class="input-text @error('availability') input-error @enderror" required>
                @foreach (['disponible' => 'Disponible', 'bajo_pedido' => 'Bajo pedido', 'agotado' => 'Agotado'] as $v => $l)
                    <option value="{{ $v }}" @selected(old('availability', $b?->availability ?? 'disponible') === $v)>{{ $l }}</option>
                @endforeach
            </select>
            @error('availability')<p class="error-message" role="alert">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="currency" class="input-label">Moneda</label>
            <select id="currency" name="currency" class="input-text">
                <option value="PEN" @selected(old('currency', $b?->currency ?? 'PEN') === 'PEN')>S/ (Soles)</option>
                <option value="USD" @selected(old('currency', $b?->currency) === 'USD')>$ (Dólares)</option>
            </select>
        </div>
        <div>
            <label for="price" class="input-label">Precio base (S/ o $)</label>
            <input type="number" id="price" name="price" min="0" step="0.01" value="{{ old('price', $b?->price) }}" class="input-text">
            @error('price')<p class="error-message" role="alert">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="price_min" class="input-label">Precio mínimo</label>
            <input type="number" id="price_min" name="price_min" min="0" step="0.01" value="{{ old('price_min', $b?->price_min) }}" class="input-text">
        </div>
        <div>
            <label for="price_max" class="input-label">Precio máximo</label>
            <input type="number" id="price_max" name="price_max" min="0" step="0.01" value="{{ old('price_max', $b?->price_max) }}" class="input-text">
            @error('price_max')<p class="error-message" role="alert">{{ $message }}</p>@enderror
        </div>
    </div>

    <fieldset>
        <legend class="input-label">Métodos de pago aceptados</legend>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            @foreach (['efectivo' => 'Efectivo', 'yape' => 'Yape', 'plin' => 'Plin', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'por_cobrar' => 'Pagar al recibir'] as $v => $l)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="payment_methods[]" value="{{ $v }}"
                           @checked(in_array($v, old('payment_methods', $b?->payment_methods ?? []), true))
                           class="rounded border-gray-300 text-purple-700">
                    {{ $l }}
                </label>
            @endforeach
        </div>
    </fieldset>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="region" class="input-label">Región / Departamento</label>
            <input type="text" id="region" name="region" value="{{ old('region', $b?->region) }}" class="input-text">
        </div>
        <div>
            <label for="province" class="input-label">Provincia</label>
            <input type="text" id="province" name="province" value="{{ old('province', $b?->province) }}" class="input-text">
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="district" class="input-label">Distrito</label>
            <input type="text" id="district" name="district" value="{{ old('district', $b?->district) }}" class="input-text">
        </div>
        <div>
            <label for="address" class="input-label">Dirección</label>
            <input type="text" id="address" name="address" value="{{ old('address', $b?->address) }}" class="input-text">
        </div>
    </div>
    <div>
        <label for="reference" class="input-label">Referencia</label>
        <input type="text" id="reference" name="reference" value="{{ old('reference', $b?->reference) }}" class="input-text">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label for="phone" class="input-label">Teléfono de contacto</label>
            <input type="tel" id="phone" name="phone" value="{{ old('phone', $b?->phone) }}" class="input-text">
            @error('phone')<p class="error-message" role="alert">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="whatsapp" class="input-label">WhatsApp</label>
            <input type="tel" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $b?->whatsapp) }}" class="input-text">
            @error('whatsapp')<p class="error-message" role="alert">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="contact_email" class="input-label">Correo de contacto</label>
            <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $b?->contact_email) }}" class="input-text">
            @error('contact_email')<p class="error-message" role="alert">{{ $message }}</p>@enderror
        </div>
    </div>

    <fieldset>
        <legend class="input-label">Horario de atención</legend>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach ($days as $key => $day)
                @php
                    $existing = $b?->hours->firstWhere('day_of_week', $key);
                @endphp
                <div class="rounded-lg border border-gray-200 p-3 space-y-2">
                    <label class="flex items-center gap-2 text-sm font-semibold">
                        <input type="checkbox" name="hours[{{ $key }}][closed]" value="1"
                               @checked(! $existing || $existing->is_closed) />
                        {{ $day }}
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="text-xs text-gray-500">Abre
                            <input type="time" name="hours[{{ $key }}][open]" value="{{ $existing && ! $existing->is_closed ? \Illuminate\Support\Carbon::parse($existing->open_time)->format('H:i') : '' }}" class="input-text !px-2 !py-1 text-sm">
                        </label>
                        <label class="text-xs text-gray-500">Cierra
                            <input type="time" name="hours[{{ $key }}][close]" value="{{ $existing && ! $existing->is_closed ? \Illuminate\Support\Carbon::parse($existing->close_time)->format('H:i') : '' }}" class="input-text !px-2 !py-1 text-sm">
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </fieldset>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="btn btn-primary">Guardar emprendimiento</button>
        <a href="{{ route('entrepreneur.businesses.index') }}" class="text-gray-600 hover:underline text-sm">Cancelar</a>
    </div>
</div>