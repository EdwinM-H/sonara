@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header title="Nuevo emprendedor" subtitle="Crea la cuenta de un emprendedor sin acceso." />

    <div class="card card-body max-w-2xl">
        <form method="POST" action="{{ route('admin.entrepreneurs.store') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="input-label">Contraseña *</label>
                    <input type="password" id="password" name="password" required class="input-text" autocomplete="new-password">
                    @error('password')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="input-label">Confirmar contraseña *</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required class="input-text" autocomplete="new-password">
                </div>
            </div>
            <div>
                <label for="personal_description" class="input-label">Descripción personal</label>
                <textarea id="personal_description" name="personal_description" rows="3" class="input-text">{{ old('personal_description') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Crear emprendedor</button>
        </form>
    </div>
@endsection