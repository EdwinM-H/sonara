@extends('layouts.guest')

@section('title', 'Crear cuenta')

@section('content')
<div>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-black">Crear tu cuenta</h1>
        <p class="text-gray-600 mt-1">Regístrate para explorar emprendimientos y solicitar productos o servicios.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4" novalidate>
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="first_name" class="input-label">Nombres</label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required
                       class="input-text w-full @error('first_name') input-error @enderror" autofocus autocomplete="given-name">
                @error('first_name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="last_name" class="input-label">Apellidos</label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required
                       class="input-text w-full @error('last_name') input-error @enderror" autocomplete="family-name">
                @error('last_name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="email" class="input-label">Correo electrónico</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                       class="input-text w-full @error('email') input-error @enderror" autocomplete="username">
                @error('email')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="phone" class="input-label">Teléfono</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required
                       class="input-text w-full @error('phone') input-error @enderror" autocomplete="tel">
                @error('phone')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="password" class="input-label">Contraseña</label>
                <input type="password" id="password" name="password" required
                       class="input-text w-full @error('password') input-error @enderror" autocomplete="new-password">
                @error('password')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="input-label">Confirmar contraseña</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       class="input-text w-full" autocomplete="new-password">
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-full">Crear cuenta</button>
    </form>

    <p class="text-center text-sm mt-6">
        ¿Ya tienes cuenta?
        <a href="{{ route('login') }}" class="text-purple-700 font-semibold hover:underline">Iniciar sesión</a>
    </p>
    <p class="text-center text-sm mt-2 text-gray-600">
        ¿Eres emprendedor con discapacidad visual?
        <a href="{{ route('voice-registration.index') }}" class="text-purple-700 font-semibold hover:underline">Regístrate por voz</a>
    </p>
</div>
@endsection
