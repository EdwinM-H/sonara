@php
    $messageTypes = [
        'success' => ['bg-green-50', 'text-green-800', 'border-green-300', 'check-circle'],
        'error' => ['bg-red-50', 'text-red-800', 'border-red-300', 'alert'],
        'info' => ['bg-blue-50', 'text-blue-800', 'border-blue-300', 'info'],
    ];
    $flashKey = session('status');
    $type = null;
    $message = null;

    if (session('success')) { $type = 'success'; $message = session('success'); }
    elseif (session('error')) { $type = 'error'; $message = session('error'); }
    elseif (session('info')) { $type = 'info'; $message = session('info'); }
@endphp

@foreach (['success', 'error', 'info'] as $t)
    @if (session($t))
        <div role="status" aria-live="polite" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
            <div class="flex items-center gap-3 rounded-xl border {{ $messageTypes[$t][0] }} {{ $messageTypes[$t][2] }} px-4 py-3">
                <span class="shrink-0" aria-hidden="true">
                    <x-icon name="{{ $messageTypes[$t][3] }}" class="w-5 h-5 {{ $messageTypes[$t][1] }}" />
                </span>
                <p class="text-sm font-medium {{ $messageTypes[$t][1] }}">{{ session($t) }}</p>
            </div>
        </div>
    @endif
@endforeach

@if ($errors->any())
    <div role="alert" aria-live="assertive" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
        <div class="rounded-xl border border-red-300 bg-red-50 px-4 py-3">
            <p class="flex items-center gap-2 font-semibold text-red-800">
                <span class="shrink-0" aria-hidden="true"><x-icon name="alert" class="w-5 h-5" /></span>
                Se encontraron errores en el formulario. Revísalos.
            </p>
            <ul class="mt-2 space-y-1">
                @foreach ($errors->all() as $error)
                    <li class="text-sm text-red-700">• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif