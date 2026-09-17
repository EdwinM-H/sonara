@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header :title="'Asistencia · '.$assistance->subject" />

    <div class="card card-body max-w-2xl mb-6">
        <x-status-badge :status="$assistance->status" />
        <dl class="mt-4 text-sm space-y-2">
            <div><dt class="font-semibold text-gray-500">Usuario</dt><dd>{{ $assistance->user?->name }} ({{ $assistance->user?->email }})</dd></div>
            <div><dt class="font-semibold text-gray-500">Canal preferido</dt><dd>{{ $assistance->preferred_channel }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Mensaje</dt><dd>{{ $assistance->message }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Fecha</dt><dd>{{ $assistance->created_at->format('d/m/Y H:i') }}</dd></div>
            @if ($assistance->admin_notes)
                <div class="rounded-lg bg-purple-50 border border-purple-200 p-3">
                    <dt class="font-semibold text-purple-800">Respuesta / notas</dt>
                    <dd class="mt-1">{{ $assistance->admin_notes }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <div class="card card-body max-w-2xl">
        <h2 class="text-lg font-bold mb-3">Actualizar estado</h2>
        <form method="POST" action="{{ route('admin.assistance.update', $assistance) }}" class="space-y-4">
            @csrf
            @method('PATCH')
            <fieldset>
                <legend class="input-label">Estado</legend>
                <div class="grid grid-cols-3 gap-2">
                    @foreach (['pendiente' => 'Pendiente', 'en_atencion' => 'En atención', 'atendida' => 'Atendida', 'cerrada' => 'Cerrada'] as $v => $l)
                        <label class="flex items-center gap-2 text-sm rounded-lg border border-gray-200 px-3 py-2 cursor-pointer">
                            <input type="radio" name="status" value="{{ $v }}" @checked($assistance->status === $v) class="text-purple-700 focus:ring-purple-600">
                            {{ $l }}
                        </label>
                    @endforeach
                </div>
                @error('status')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </fieldset>
            <div>
                <label for="admin_notes" class="input-label">Respuesta o notas</label>
                <textarea id="admin_notes" name="admin_notes" rows="4" class="input-text">{{ old('admin_notes', $assistance->admin_notes) }}</textarea>
                @error('admin_notes')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn btn-primary">Actualizar solicitud</button>
        </form>
    </div>
@endsection