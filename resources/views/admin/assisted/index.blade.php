@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header title="Registro asistido" subtitle="Crea en un solo paso la cuenta, el emprendimiento y una publicación de un nuevo emprendedor." />

    <div class="card card-body max-w-3xl">
        <form method="POST" action="{{ route('admin.assisted.store') }}" class="space-y-5">
            @csrf

            <fieldset>
                <legend class="font-bold text-gray-800">Datos del emprendedor</legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-3">
                    <div>
                        <label for="first_name" class="input-label">Nombres *</label>
                        <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required class="input-text @error('first_name') input-error @enderror">
                        @error('first_name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="last_name" class="input-label">Apellidos *</label>
                        <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required class="input-text @error('last_name') input-error @enderror">
                        @error('last_name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="input-label">Correo *</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required class="input-text @error('email') input-error @enderror">
                        @error('email')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="phone" class="input-label">Teléfono *</label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required class="input-text @error('phone') input-error @enderror">
                        @error('phone')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password" class="input-label">Contraseña *</label>
                        <input type="password" id="password" name="password" required class="input-text">
                        @error('password')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="input-label">Confirmar contraseña *</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required class="input-text">
                    </div>
                </div>
                <div class="mt-3">
                    <label for="personal_description" class="input-label">Descripción personal</label>
                    <textarea id="personal_description" name="personal_description" rows="2" class="input-text">{{ old('personal_description') }}</textarea>
                </div>
            </fieldset>

            <hr class="border-gray-200">

            <fieldset>
                <legend class="font-bold text-gray-800">Emprendimiento</legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-3">
                    <div>
                        <label for="business_name" class="input-label">Nombre *</label>
                        <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}" required class="input-text @error('business_name') input-error @enderror">
                        @error('business_name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="category_id" class="input-label">Categoría</label>
                        <select id="category_id" name="category_id" class="input-text">
                            <option value="">Sin categoría</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>{{ $cat->icon }} {{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="type" class="input-label">Tipo *</label>
                        <select id="type" name="type" required class="input-text">
                            <option value="producto" @selected(old('type') === 'producto')>Producto</option>
                            <option value="servicio" @selected(old('type') === 'servicio')>Servicio</option>
                        </select>
                    </div>
                    <div>
                        <label for="phone_business" class="input-label">Teléfono del negocio</label>
                        <input type="tel" id="phone_business" name="phone_business" value="{{ old('phone_business') }}" class="input-text">
                    </div>
                    <div>
                        <label for="whatsapp" class="input-label">WhatsApp</label>
                        <input type="tel" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" class="input-text">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                    <div><label for="region" class="input-label">Región</label><input type="text" id="region" name="region" value="{{ old('region', 'Cusco') }}" class="input-text"></div>
                    <div><label for="province" class="input-label">Provincia</label><input type="text" id="province" name="province" value="{{ old('province') }}" class="input-text"></div>
                    <div><label for="district" class="input-label">Distrito</label><input type="text" id="district" name="district" value="{{ old('district') }}" class="input-text"></div>
                </div>
                <div class="mt-3">
                    <label for="business_description" class="input-label">Descripción del emprendimiento</label>
                    <textarea id="business_description" name="business_description" rows="2" class="input-text">{{ old('business_description') }}</textarea>
                </div>
            </fieldset>

            <hr class="border-gray-200">

            <fieldset>
                <legend class="font-bold text-gray-800">Primera publicación</legend>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-3">
                    <div class="sm:col-span-2">
                        <label for="publication_name" class="input-label">Nombre *</label>
                        <input type="text" id="publication_name" name="publication_name" value="{{ old('publication_name') }}" required class="input-text @error('publication_name') input-error @enderror">
                        @error('publication_name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="publication_type" class="input-label">Tipo *</label>
                        <select id="publication_type" name="publication_type" required class="input-text">
                            <option value="producto" @selected(old('publication_type') === 'producto')>Producto</option>
                            <option value="servicio" @selected(old('publication_type') === 'servicio')>Servicio</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <label for="publication_price" class="input-label">Precio (S/)</label>
                    <input type="number" id="publication_price" name="publication_price" min="0" step="0.01" value="{{ old('publication_price') }}" class="input-text">
                </div>
            </fieldset>

            <p class="text-sm text-gray-500">
                Al completar, se iniciará el plazo de documentación del emprendedor (según la configuración del sistema).
            </p>

            <button type="submit" class="btn btn-primary w-full sm:w-auto">Completar registro asistido</button>
        </form>
    </div>
@endsection