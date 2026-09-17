<section class="card card-body">
    <h2 class="text-lg font-bold text-gray-900">Información personal</h2>
    <p class="mt-1 text-sm text-gray-600">Actualiza tus datos y correo electrónico.</p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="first_name" class="input-label">Nombres</label>
                <input type="text" id="first_name" name="first_name" class="input-text" value="{{ old('first_name', $user->first_name) }}" required autofocus autocomplete="given-name">
                @error('first_name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="last_name" class="input-label">Apellidos</label>
                <input type="text" id="last_name" name="last_name" class="input-text" value="{{ old('last_name', $user->last_name) }}" required autocomplete="family-name">
                @error('last_name')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="phone" class="input-label">Teléfono</label>
            <input type="tel" id="phone" name="phone" class="input-text" value="{{ old('phone', $user->phone) }}" autocomplete="tel">
            @error('phone')<p class="error-message" role="alert">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="input-label">Correo electrónico</label>
            <input type="email" id="email" name="email" class="input-text" value="{{ old('email', $user->email) }}" required autocomplete="username">
            @error('email')<p class="error-message" role="alert">{{ $message }}</p>@enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-sm text-gray-800">
                        Tu correo aún no está verificado.
                        <button form="send-verification" class="underline text-purple-700 font-semibold">Reenviar verificación</button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm text-green-600">Se envió un nuevo enlace de verificación a tu correo.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn btn-primary">Guardar</button>
            @if (session('status') === 'profile-updated')
                <p class="text-sm text-green-700" x-data x-init="$el.textContent = 'Guardado.'; setTimeout(() => $el.textContent = '', 2000)">Guardado.</p>
            @endif
        </div>
    </form>
</section>