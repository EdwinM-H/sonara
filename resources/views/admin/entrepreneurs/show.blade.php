@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header :title="'Emprendedor · '.$user->name" subtitle="Detalle, verificación y actividad del emprendedor." />

    {{-- Estado de verificación --}}
    <div class="card card-body mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <x-status-badge :status="$profile->verification_status" />
                @if ($profile->isVerified())
                    <span class="text-sm text-green-700">✓ Verificado</span>
                @endif
            </div>
            <div class="flex flex-wrap gap-2 text-sm text-gray-600">
                <span>Días restantes (documento): <strong>{{ $documentDays }}</strong></span>
                <span>·</span>
                <span>Revisión: <strong>{{ $validationDays }}</strong> días</span>
            </div>
        </div>

        @if (! $profile->isVerified())
            <div class="mt-4 flex flex-wrap gap-2">
                @if (in_array($profile->verification_status, ['pendiente_documento', 'documento_enviado', 'rechazado'], true) && $documents->isNotEmpty())
                    <form method="POST" action="{{ route('admin.entrepreneurs.approve', $user) }}" onsubmit="return confirm('¿Aprobar y verificar a {{ $user->name }}?');">
                        @csrf
                        <button type="submit" class="btn btn-success">✓ Aprobar verificación</button>
                    </form>
                @endif
                @if (in_array($profile->verification_status, ['documento_enviado', 'en_revision'], true))
                    <form method="POST" action="{{ route('admin.entrepreneurs.correction', $user) }}" class="flex items-center gap-2" x-data="{ note: '' }">
                        @csrf
                        <label for="correction_note" class="sr-only">Nota de corrección</label>
                        <input type="text" id="correction_note" name="correction_note" x-model="note" required placeholder="¿Qué debe corregir?" class="input-text !py-1.5 text-sm w-64">
                        <button type="submit" class="btn btn-secondary">Solicitar corrección</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('admin.entrepreneurs.reject', $user) }}" class="flex items-center gap-2" x-data="{ reason: '' }">
                    @csrf
                    <label for="reason" class="sr-only">Motivo del rechazo</label>
                    <input type="text" id="reason" name="reason" x-model="reason" required placeholder="Motivo del rechazo" class="input-text !py-1.5 text-sm w-64">
                    <button type="submit" class="btn btn-danger">Rechazar</button>
                </form>
            </div>
        @endif
        @if ($errors->any())
            <div class="mt-3">
                @foreach ($errors->all() as $error)
                    <p class="error-message" role="alert">{{ $error }}</p>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Datos --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card card-body">
            <h2 class="text-lg font-bold mb-3">Datos personales</h2>
            <dl class="text-sm space-y-2">
                <div><dt class="font-semibold text-gray-500">Nombre</dt><dd>{{ $user->name }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Correo</dt><dd>{{ $user->email }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Teléfono</dt><dd>{{ $user->phone ?? '—' }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Descripción</dt><dd>{{ $profile->personal_description ?? '—' }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Estado</dt><dd><x-status-badge :status="$user->status" /></dd></div>
            </dl>
            <a href="{{ route('admin.entrepreneurs.edit', $user) }}" class="btn btn-outline mt-4">Editar</a>
        </div>

        <div class="card card-body">
            <h2 class="text-lg font-bold mb-3">Documentos</h2>
            @if ($documents->isEmpty())
                <p class="text-gray-500 text-sm">Sin documentos enviados.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($documents as $doc)
                        <li class="flex items-center justify-between border-b border-gray-100 pb-2">
                            <span class="truncate">{{ $doc->original_name }}</span>
                            <span class="flex items-center gap-2">
                                <span class="text-xs text-gray-400">{{ $doc->uploaded_at?->format('d/m/Y') }}</span>
                                <a href="{{ route('admin.entrepreneurs.documents.show', $doc) }}" class="btn btn-sm btn-outline">Descargar</a>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Emprendimientos --}}
    <div class="card card-body mt-6">
        <h2 class="text-lg font-bold mb-3">Emprendimientos ({{ $businesses->count() }})</h2>
        @if ($businesses->isEmpty())
            <p class="text-gray-500 text-sm">Sin emprendimientos.</p>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach ($businesses as $biz)
                    <li class="py-2 flex items-center justify-between gap-2">
                        <span class="font-medium">{{ $biz->name }} <span class="text-xs text-gray-400">({{ $biz->publications_count }} publicaciones)</span></span>
                        <x-status-badge :status="$biz->status" />
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Publicaciones --}}
    <div class="card card-body mt-6">
        <h2 class="text-lg font-bold mb-3">Publicaciones</h2>
        @if ($publications->isEmpty())
            <p class="text-gray-500 text-sm">Sin publicaciones.</p>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach ($publications as $pub)
                    <li class="py-2 flex items-center justify-between gap-2">
                        <span class="font-medium">{{ $pub->name }}</span>
                        <x-status-badge :status="$pub->status" />
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Asistencia --}}
    <div class="card card-body mt-6">
        <h2 class="text-lg font-bold mb-3">Solicitudes de asistencia</h2>
        @if ($assistance->isEmpty())
            <p class="text-gray-500 text-sm">Sin solicitudes de asistencia.</p>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach ($assistance as $a)
                    <li class="py-2 flex items-center justify-between gap-2 text-sm">
                        <span class="font-medium truncate">{{ Str::limit($a->subject, 60) }}</span>
                        <x-status-badge :status="$a->status" />
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection