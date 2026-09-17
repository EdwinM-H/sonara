@extends('layouts.guest')

@section('title', 'Crear cuenta')

@section('content')
@php
    $initialStep = 1;
    if ($errors->any()) {
        if (old('password_confirmation') !== null) {
            $initialStep = 3;
        } elseif (old('business_name') !== null || old('business_description') !== null || old('category_id') !== null) {
            $initialStep = 2;
        }
    }
@endphp

<div x-data="{
    step: {{ $initialStep }},
    role: '{{ old('role', 'customer') }}',
    get effectiveMax() {
        return this.role === 'entrepreneur' ? 3 : 3;
    },
    next() {
        if (this.role === 'entrepreneur') {
            this.step = Math.min(this.step + 1, 3);
        } else {
            this.step = 3;
        }
    },
    back() {
        this.step = Math.max(this.step - 1, 1);
    }
}">
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-black">Crear tu cuenta</h1>
        <p class="text-gray-600 mt-1">Regístrate para explorar y solicitar, o para emprender con SONARA.</p>
    </div>

    {{-- Indicador de progreso --}}
    <ol class="flex items-center justify-center gap-2 mb-6" aria-label="Pasos del registro">
        <li class="flex items-center gap-2">
            <span :class="step >= 1 ? 'bg-purple-700 text-white' : 'bg-gray-200 text-gray-500'" class="grid place-items-center w-8 h-8 rounded-full text-sm font-bold">1</span>
            <span class="text-xs font-semibold text-gray-600" :class="step >= 1 ? 'text-purple-800' : ''">Cuenta</span>
        </li>
        <li class="flex-1 h-px max-w-10 bg-gray-300" aria-hidden="true"></li>
        <li class="flex items-center gap-2">
            <span :class="step >= 2 ? 'bg-purple-700 text-white' : 'bg-gray-200 text-gray-500'" class="grid place-items-center w-8 h-8 rounded-full text-sm font-bold">2</span>
            <span class="text-xs font-semibold text-gray-600" :class="step >= 2 ? 'text-purple-800' : ''">Emprendimiento</span>
        </li>
        <li class="flex-1 h-px max-w-10 bg-gray-300" aria-hidden="true"></li>
        <li class="flex items-center gap-2">
            <span :class="step >= 3 ? 'bg-purple-700 text-white' : 'bg-gray-200 text-gray-500'" class="grid place-items-center w-8 h-8 rounded-full text-sm font-bold">3</span>
            <span class="text-xs font-semibold text-gray-600" :class="step >= 3 ? 'text-purple-800' : ''">Acceso</span>
        </li>
    </ol>

    <form method="POST" action="{{ route('register') }}" class="space-y-4" novalidate>
        @csrf

        {{-- Paso 1: rol + datos personales --}}
        <div x-show="step === 1" x-cloak class="space-y-4 fade-in">
            <fieldset>
                <legend class="input-label">¿Qué tipo de cuenta deseas crear?</legend>
                <div class="grid grid-cols-2 gap-3">
                    <label class="card cursor-pointer !p-4 {{ old('role') == 'customer' ? '!border-purple-700 bg-purple-50' : '' }}" :class="role === 'customer' ? '!border-purple-700 bg-purple-50' : ''">
                        <input type="radio" name="role" value="customer" x-model="role" class="sr-only">
                        <span class="grid place-items-center w-10 h-10 rounded-xl bg-purple-100 text-purple-700" aria-hidden="true"><x-icon name="search" /></span>
                        <p class="mt-2 font-bold text-sm">Cliente / Usuario</p>
                        <p class="text-xs text-gray-600 mt-0.5">Quiero explorar y solicitar productos o servicios.</p>
                    </label>
                    <label class="card cursor-pointer !p-4 {{ old('role') == 'entrepreneur' ? '!border-purple-700 bg-purple-50' : '' }}" :class="role === 'entrepreneur' ? '!border-purple-700 bg-purple-50' : ''">
                        <input type="radio" name="role" value="entrepreneur" x-model="role" class="sr-only">
                        <span class="grid place-items-center w-10 h-10 rounded-xl bg-purple-100 text-purple-700" aria-hidden="true"><x-icon name="box" /></span>
                        <p class="mt-2 font-bold text-sm">Emprendedor</p>
                        <p class="text-xs text-gray-600 mt-0.5">Quiero crear mi emprendimiento y publicar.</p>
                    </label>
                </div>
                @error('role')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </fieldset>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="input-label">Nombres</label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required
                           class="input-text @error('first_name') input-error @enderror" autofocus autocomplete="given-name">
                    @error('first_name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="last_name" class="input-label">Apellidos</label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required
                           class="input-text @error('last_name') input-error @enderror" autocomplete="family-name">
                    @error('last_name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="email" class="input-label">Correo electrónico</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                           class="input-text @error('email') input-error @enderror" autocomplete="username">
                    @error('email')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="phone" class="input-label">Teléfono</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required
                           class="input-text @error('phone') input-error @enderror" autocomplete="tel">
                    @error('phone')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="button" class="btn btn-primary" @click="next()">
                    Siguiente
                    <x-icon name="arrow-right" class="w-4 h-4" />
                </button>
            </div>
        </div>

        {{-- Paso 2: emprendedor --}}
        <div x-show="step === 2 && role === 'entrepreneur'" x-cloak class="space-y-4 fade-in">
            <div class="space-y-4">
                <div>
                    <label for="business_name" class="input-label">Nombre del emprendimiento</label>
                    <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}"
                           class="input-text @error('business_name') input-error @enderror">
                    @error('business_name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="business_description" class="input-label">Descripción breve</label>
                    <textarea id="business_description" name="business_description" rows="3"
                              class="input-text">{{ old('business_description') }}</textarea>
                </div>
                <div>
                    <label for="category_id" class="input-label">Categoría</label>
                    <select id="category_id" name="category_id" class="input-text">
                        <option value="">Selecciona una categoría</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>{{ $cat->icon }} {{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex justify-between pt-2">
                <button type="button" class="btn btn-secondary" @click="back()">
                    <x-icon name="chevron-right" class="w-4 h-4 rotate-180" /> Atrás
                </button>
                <button type="button" class="btn btn-primary" @click="next()">
                    Siguiente
                    <x-icon name="arrow-right" class="w-4 h-4" />
                </button>
            </div>
        </div>

        {{-- Paso 3: acceso --}}
        <div x-show="step === 3" x-cloak class="space-y-4 fade-in">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="input-label">Contraseña</label>
                    <input type="password" id="password" name="password" required
                           class="input-text @error('password') input-error @enderror" autocomplete="new-password">
                    @error('password')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="input-label">Confirmar contraseña</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                           class="input-text" autocomplete="new-password">
                </div>
            </div>

            <div class="flex justify-between pt-2">
                <button type="button" class="btn btn-secondary" @click="back()">
                    <x-icon name="chevron-right" class="w-4 h-4 rotate-180" /> Atrás
                </button>
                <button type="submit" class="btn btn-primary">Crear cuenta</button>
            </div>
        </div>
    </form>

    <p class="text-center text-sm mt-6">
        ¿Ya tienes cuenta?
        <a href="{{ route('login') }}" class="text-purple-700 font-semibold hover:underline">Iniciar sesión</a>
        ·
        <a href="{{ route('voice-registration.index') }}" class="text-purple-700 font-semibold hover:underline">Registro por voz</a>
    </p>
</div>
@endsection