<x-guest-layout>
    <section aria-labelledby="login-title">
        <h1 id="login-title" class="text-2xl font-black">Iniciar sesión</h1>
        <p class="text-gray-600 mt-1 mb-6">Ingresa con tu correo y contraseña para continuar.</p>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="input-label">Correo electrónico</label>
                <div class="input-group">
                    <span class="input-icon"><x-icon name="user" /></span>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           class="input-text @error('email') input-error @enderror">
                </div>
                @error('email')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="input-label">Contraseña</label>
                <div class="relative" x-data="{ show: false }">
                    <input :type="show ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password"
                           class="input-text pr-12 @error('password') input-error @enderror">
                    <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-purple-700"
                            :aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'" aria-controls="password">
                        <x-icon name="eye" class="w-5 h-5" />
                    </button>
                </div>
                @error('password')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-purple-700 focus:ring-purple-600">
                    Recordarme
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm text-purple-700 font-semibold hover:underline">
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif
            </div>

            <button type="submit" class="btn btn-primary w-full mt-2">Iniciar sesión</button>

            <p class="text-center text-sm text-gray-600 mt-4">
                ¿No tienes cuenta?
                <a href="{{ route('register') }}" class="text-purple-700 font-semibold hover:underline">Regístrate aquí</a>
                ·
                <a href="{{ route('voice-registration.index') }}" class="text-purple-700 font-semibold hover:underline">Registro por voz</a>
            </p>
        </form>

        <div class="mt-6 rounded-xl bg-purple-50 border border-purple-200 p-4 text-sm text-gray-700 space-y-1">
            <p class="font-semibold text-purple-800 flex items-center gap-1.5">
                <x-icon name="sparkles" class="w-4 h-4" /> Cuentas de demostración
            </p>
            <p>Emprendedor: <code>emprendedor@sonara.test</code></p>
            <p>Cliente: <code>cliente@sonara.test</code></p>
            <p>Admin: <code>admin@sonara.test</code></p>
            <p>Contraseña: <code>Password123!</code></p>
        </div>
    </section>
</x-guest-layout>