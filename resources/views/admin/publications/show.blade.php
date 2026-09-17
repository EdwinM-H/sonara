@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header :title="'Revisar publicación · '.$publication->name" subtitle="Avalúa el contenido y decide su estado." />

    @if ($publication->rejection_reason && $publication->status === 'rechazada')
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 mb-6">
            <p class="font-semibold">Motivo de rechazo registrado</p>
            <p class="mt-1 text-sm">{{ $publication->rejection_reason }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card card-body">
            <h2 class="text-lg font-bold mb-3">Datos</h2>
            <dl class="text-sm space-y-2">
                <div><dt class="font-semibold text-gray-500">Nombre</dt><dd>{{ $publication->name }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Descripción</dt><dd>{{ $publication->description ?? '—' }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Tipo</dt><dd>{{ ucfirst($publication->type) }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Precio</dt><dd>{{ $publication->price_display }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Emprendimiento</dt><dd>{{ $publication->business?->name }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Emprendedor</dt><dd>{{ $publication->business?->entrepreneurProfile?->user?->name ?? '—' }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Estado actual</dt><dd><x-status-badge :status="$publication->status" /></dd></div>
            </dl>
        </div>

        <div class="card card-body">
            <h2 class="text-lg font-bold mb-3">Flyer</h2>
            @if ($publication->flyer_image)
                <img src="{{ asset($publication->flyer_image) }}" alt="Flyer de {{ $publication->name }}" class="rounded-xl border border-gray-200 w-full">
            @else
                <p class="text-gray-500 text-sm">Sin flyer asignado.</p>
            @endif

            @if ($publication->aiGenerations->isNotEmpty())
                <p class="text-sm font-semibold text-gray-600 mt-4 mb-2">Historial de generaciones ({{ $publication->aiGenerations->count() }})</p>
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($publication->aiGenerations->take(6) as $gen)
                        <div class="rounded-lg border border-gray-200 overflow-hidden">
                            <img src="{{ asset($gen->image_path) }}" alt="Generación" class="w-full h-20 object-cover">
                            <p class="text-xs p-1 text-center">{{ $gen->provider_name }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Formulario de cambio de estado --}}
    <div class="card card-body mt-6">
        <h2 class="text-lg font-bold mb-3">Cambiar estado</h2>

        @if (! in_array($publication->status, array_keys($publication::TRANSITIONS[$publication->status] ?? [])))
            <p class="text-gray-500 text-sm">La publicación <strong class="text-gray-800">{{ $publication->status }}</strong> no permite transiciones.</p>
        @else
            <form method="POST" action="{{ route('admin.publications.changeStatus', $publication) }}" class="space-y-4">
                @csrf
                <div class="flex flex-wrap gap-2">
                    @foreach (($publication::TRANSITIONS[$publication->status] ?? []) as $target)
                        <label class="flex items-center gap-2 text-sm rounded-lg border border-gray-200 px-3 py-2 cursor-pointer hover:bg-purple-50">
                            <input type="radio" name="status" value="{{ $target }}" class="text-purple-700 focus:ring-purple-600" required>
                            @switch($target)
                                @case('publicada')
                                    <span class="text-green-700 font-semibold">✓ Publicar</span>
                                    @break
                                @case('rechazada')
                                    <span class="text-red-700 font-semibold">✕ Rechazar</span>
                                    @break
                                @case('suspendida')
                                    <span class="text-orange-700 font-semibold">⏸ Suspender</span>
                                    @break
                                @default
                                    <span>{{ $target }}</span>
                            @endswitch
                        </label>
                    @endforeach
                </div>
                @error('status')<p class="error-message" role="alert">{{ $message }}</p>@enderror

                <div>
                    <label for="rejection_reason" class="input-label">Motivo (obligatorio si rechazas)</label>
                    <textarea id="rejection_reason" name="rejection_reason" rows="3" class="input-text" placeholder="Explica por qué rechazas o suspendes la publicación."></textarea>
                    @error('rejection_reason')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn btn-primary">Confirmar cambio de estado</button>
            </form>
        @endif
    </div>
@endsection