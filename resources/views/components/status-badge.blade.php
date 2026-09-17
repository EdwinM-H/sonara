@props(['status'])

@php
    // Clases spec §2: aprobado (verde), rechazado (rojo), revisión/alerta (ámbar), borrador/informativo (morado)
    $classes = [
        'borrador' => 'badge-draft',
        'pendiente' => 'badge-review',
        'publicada' => 'badge-approved',
        'rechazada' => 'badge-rejected',
        'suspendida' => 'badge-review',
        'aprobado' => 'badge-approved',
        'rechazado' => 'badge-rejected',
        'en_revision' => 'badge-draft',
        'documento_enviado' => 'badge-draft',
        'pendiente_documento' => 'badge-review',
        'enviada' => 'badge-draft',
        'vista' => 'badge-review',
        'aceptada' => 'badge-approved',
        'rechazada_s' => 'badge-rejected',
        'completada' => 'badge-approved',
        'cancelada' => 'badge-neutral',
        'activo' => 'badge-approved',
        'inactivo' => 'badge-neutral',
        'en_atencion' => 'badge-draft',
        'atendida' => 'badge-approved',
        'cerrada' => 'badge-neutral',
    ];
    $symbols = [
        'borrador' => '◆',
        'pendiente' => '◔',
        'publicada' => '✓',
        'rechazada' => '✕',
        'suspendida' => '◔',
        'aprobado' => '✓',
        'rechazado' => '✕',
        'en_revision' => '◆',
        'documento_enviado' => '◆',
        'pendiente_documento' => '◔',
        'enviada' => '◆',
        'vista' => '◔',
        'aceptada' => '✓',
        'rechazada_s' => '✕',
        'completada' => '✓',
        'cancelada' => '·',
        'activo' => '✓',
        'inactivo' => '·',
        'en_atencion' => '◆',
        'atendida' => '✓',
        'cerrada' => '·',
    ];
    $labels = [
        'borrador' => 'Borrador',
        'pendiente' => 'Pendiente',
        'publicada' => 'Publicada',
        'rechazada' => 'Rechazada',
        'suspendida' => 'Suspendida',
        'aprobado' => 'Aprobado',
        'rechazado' => 'Rechazado',
        'en_revision' => 'En revisión',
        'documento_enviado' => 'Documento enviado',
        'pendiente_documento' => 'Pendiente de documento',
        'enviada' => 'Enviada',
        'vista' => 'Vista',
        'aceptada' => 'Aceptada',
        'rechazada_s' => 'Rechazada',
        'completada' => 'Completada',
        'cancelada' => 'Cancelada',
        'activo' => 'Activo',
        'inactivo' => 'Inactivo',
        'en_atencion' => 'En atención',
        'atendida' => 'Atendida',
        'cerrada' => 'Cerrada',
    ];
    $class = $classes[$status] ?? 'badge-neutral';
    $symbol = $symbols[$status] ?? '·';
@endphp

<span class="badge {{ $class }}">
    <span aria-hidden="true">{{ $symbol }}</span> {{ $labels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
</span>
