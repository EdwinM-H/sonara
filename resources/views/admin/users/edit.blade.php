@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header :title="'Editar usuario · '.$user->name" />

    <div class="card card-body max-w-2xl">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="input-label">Nombres</label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required class="input-text @error('first_name') input-error @enderror">
                    @error('first_name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="last_name" class="input-label">Apellidos</label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required class="input-text @error('last_name') input-error @enderror">
                    @error('last_name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="email" class="input-label">Correo electrónico</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="input-text @error('email') input-error @enderror">
                @error('email')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="phone" class="input-label">Teléfono</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="input-text">
            </div>

            <div>
                <label for="role" class="input-label">Rol</label>
                <select id="role" name="role" class="input-text">
                    <option value="customer" @selected($user->hasRole('customer'))>Cliente</option>
                    <option value="entrepreneur" @selected($user->hasRole('entrepreneur'))>Emprendedor</option>
                    <option value="admin" @selected($user->hasRole('admin'))>Administrador</option>
                </select>
                @error('role')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="{{ route('admin.users') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection