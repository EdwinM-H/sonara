@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header :title="'Editar emprendedor · '.$user->name" />

    <div class="card card-body max-w-2xl">
        <form method="POST" action="{{ route('admin.entrepreneurs.update', $user) }}" class="space-y-4">
            @csrf
            @method('PATCH')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="input-label">Nombres *</label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required class="input-text @error('first_name') input-error @enderror">
                    @error('first_name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="last_name" class="input-label">Apellidos *</label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required class="input-text @error('last_name') input-error @enderror">
                    @error('last_name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label for="email" class="input-label">Correo *</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="input-text @error('email') input-error @enderror">
                @error('email')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="phone" class="input-label">Teléfono</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="input-text">
            </div>
            <div>
                <label for="personal_description" class="input-label">Descripción personal</label>
                <textarea id="personal_description" name="personal_description" rows="3" class="input-text">{{ old('personal_description', $user->entrepreneurProfile?->personal_description) }}</textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="{{ route('admin.entrepreneurs.show', $user) }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection