@extends('layouts.panel-entrepreneur')

@section('panel-content')
    <x-panel-header title="Mi perfil" subtitle="Actualiza tus datos personales y tu descripción." />

    @if ($profile && ! $profile->isVerified())
        <div role="alert" class="rounded-xl mb-6 border border-purple-200 bg-purple-50 p-4 text-purple-900">
            <p class="font-semibold">Tu perfil está {{ str_replace('_', ' ', $profile->verification_status) }}.</p>
            <p class="mt-1 text-sm">Cuando completes tu documentación serás visible como emprendedor verificado en el portal.</p>
        </div>
    @endif

    <div class="card card-body max-w-2xl">
        <form method="POST" action="{{ route('entrepreneur.profile.update') }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="input-label">Nombres</label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" class="input-text @error('first_name') input-error @enderror">
                    @error('first_name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="last_name" class="input-label">Apellidos</label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" class="input-text @error('last_name') input-error @enderror">
                    @error('last_name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="phone" class="input-label">Teléfono</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="input-text @error('phone') input-error @enderror">
                @error('phone')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="personal_description" class="input-label">Cuéntanos sobre ti</label>
                <textarea id="personal_description" name="personal_description" rows="4" class="input-text">{{ old('personal_description', $profile?->personal_description) }}</textarea>
            </div>

            <hr class="border-gray-200">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="input-label">Nueva contraseña (opcional)</label>
                    <input type="password" id="password" name="password" class="input-text" autocomplete="new-password">
                    @error('password')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="input-label">Confirmar nueva contraseña</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="input-text" autocomplete="new-password">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </form>
    </div>
@endsection