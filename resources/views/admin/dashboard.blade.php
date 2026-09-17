@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header title="Panel de administración" subtitle="Resumen del estado del sistema." />

    {{-- Alertas --}}
    @if ($stats['pending_verifications'] > 0 || $stats['pending_publications'] > 0 || $stats['assistance'] > 0 || $stats['expected_requests'] > 0)
        <div role="alert" class="rounded-2xl mb-6 border-[1.5px] p-5 flex gap-4" style="background-color: var(--color-warning-soft); border-color: var(--color-warning-border); color: var(--color-warning)">
            <span class="grid place-items-center w-10 h-10 rounded-xl bg-white shrink-0" aria-hidden="true">
                <x-icon name="alert" />
            </span>
            <div>
                <p class="font-bold">Acciones pendientes por tu atención</p>
                <ul class="mt-2 list-disc list-inside text-sm space-y-1">
                    @if ($stats['pending_verifications'] > 0)
                        <li><a href="{{ route('admin.entrepreneurs.index') }}" class="underline font-semibold">{{ $stats['pending_verifications'] }} emprendimiento(s) por revisar (verificación).</a></li>
                    @endif
                    @if ($stats['pending_publications'] > 0)
                        <li><a href="{{ route('admin.publications.index') }}" class="underline font-semibold">{{ $stats['pending_publications'] }} publicación(es) pendiente(s) de revisión.</a></li>
                    @endif
                    @if ($stats['expected_requests'] > 0)
                        <li><a href="{{ route('admin.requests') }}" class="underline font-semibold">{{ $stats['expected_requests'] }} solicitud(es) de clientes sin responder.</a></li>
                    @endif
                    @if ($stats['assistance'] > 0)
                        <li><a href="{{ route('admin.assistance.index') }}" class="underline font-semibold">{{ $stats['assistance'] }} solicitud(es) de asistencia.</a></li>
                    @endif
                </ul>
            </div>
        </div>
    @endif

    {{-- Estadísticas --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <x-stat-card label="Usuarios" :value="$stats['users']" icon="user" />
        <x-stat-card label="Emprendedores" :value="$stats['entrepreneurs']" icon="sparkles" />
        <x-stat-card label="Clientes" :value="$stats['customers']" icon="heart" />
        <x-stat-card label="Emprendimientos" :value="$stats['businesses']" icon="tag" />
        <x-stat-card label="Publicaciones" :value="$stats['publications']" icon="box" />
        <x-stat-card label="Solicitudes" :value="$stats['requests']" icon="clipboard" />
        <x-stat-card label="Verif. pendientes" :value="$stats['pending_verifications']" icon="document" accent="text-amber-700" />
        <x-stat-card label="Asistencia" :value="$stats['assistance']" icon="chat" accent="text-amber-700" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <section aria-labelledby="adm-recent-users" class="card card-body">
            <h2 id="adm-recent-users" class="section-title !mb-0 mb-3">Usuarios recientes</h2>
            <ul class="space-y-2">
                @foreach ($recentUsers as $u)
                    <li class="flex items-center justify-between gap-2 text-sm">
                        <span class="font-medium truncate">{{ $u->name }}</span>
                        <span class="flex items-center gap-2">
                            @foreach ($u->roles as $role)
                                <span class="badge badge-neutral">{{ $role->name }}</span>
                            @endforeach
                            <span class="text-xs text-gray-400">{{ $u->created_at->format('d/m/Y') }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section aria-labelledby="adm-businesses-cat" class="card card-body">
            <h2 id="adm-businesses-cat" class="section-title !mb-0 mb-3">Emprendimientos por categoría</h2>
            @if ($businessesByCategory->isEmpty())
                <p class="text-gray-500 text-sm">Sin datos aún.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($businessesByCategory as $cat => $total)
                        <li class="flex justify-between items-center gap-3">
                            <span>{{ $cat }}</span>
                            <span class="badge badge-neutral">{{ $total }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <section aria-labelledby="adm-recent-assistance" class="card card-body">
            <h2 id="adm-recent-assistance" class="section-title !mb-0 mb-3">Últimas asistencias</h2>
            @if ($recentAssistance->isEmpty())
                <p class="text-gray-500 text-sm">Sin solicitudes de asistencia.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($recentAssistance as $a)
                        <li class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium truncate">{{ Str::limit($a->subject, 40) }}</span>
                            <x-status-badge :status="$a->status" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section aria-labelledby="adm-recent-publications" class="card card-body">
            <h2 id="adm-recent-publications" class="section-title !mb-0 mb-3">Últimas publicaciones</h2>
            @if ($recentPublications->isEmpty())
                <p class="text-gray-500 text-sm">Sin publicaciones aún.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($recentPublications as $p)
                        <li class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium truncate">{{ $p->name }} <span class="text-gray-400">· {{ $p->business?->name }}</span></span>
                            <x-status-badge :status="$p->status" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <div class="card card-body mt-6">
        <h2 class="section-title !mb-0 mb-3">Solicitudes por estado</h2>
        <div class="flex flex-wrap gap-2">
            @foreach ([
                \App\Models\Request::STATUS_ENVIADA => 'Enviadas',
                \App\Models\Request::STATUS_VISTA => 'Vistas',
                \App\Models\Request::STATUS_ACEPTADA => 'Aceptadas',
                \App\Models\Request::STATUS_RECHAZADA => 'Rechazadas',
                \App\Models\Request::STATUS_COMPLETADA => 'Completadas',
                \App\Models\Request::STATUS_CANCELADA => 'Canceladas',
            ] as $status => $label)
                <span class="badge badge-neutral">{{ $label }}: <strong>{{ $requestsByStatus[$status] ?? 0 }}</strong></span>
            @endforeach
        </div>
    </div>

    {{-- Validaciones pendientes --}}
    <section aria-labelledby="adm-validations" class="card card-body mt-6 !p-0 overflow-hidden">
        <div class="!p-5 sm:!p-6 pb-0">
            <h2 id="adm-validations" class="text-lg font-display font-bold">Validaciones pendientes</h2>
            <p class="text-sm text-gray-500 mt-1">Documentación de emprendedores esperando revisión.</p>
        </div>
        @if ($pendingValidations->isEmpty())
            <p class="text-gray-500 text-sm !p-5 sm:!p-6">No hay validaciones pendientes en este momento.</p>
        @else
            <div class="overflow-x-auto mt-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Emprendedor</th>
                            <th>Documento</th>
                            <th>Plazo</th>
                            <th>Estado</th>
                            <th class="sr-only">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingValidations as $profile)
                            <tr>
                                <td class="font-semibold">{{ $profile->user?->name }}</td>
                                <td>{{ $profile->latestDocument?->original_name ?? 'Sin documento cargado' }}</td>
                                <td>
                                    @if ($profile->daysRemainingForDocument() !== null)
                                        {{ $profile->daysRemainingForDocument() }} día(s)
                                    @elseif ($profile->daysRemainingForValidation() !== null)
                                        {{ $profile->daysRemainingForValidation() }} día(s) (validación)
                                    @else
                                        —
                                    @endif
                                </td>
                                <td><x-status-badge :status="$profile->verification_status" /></td>
                                <td class="text-right">
                                    <a href="{{ route('admin.entrepreneurs.show', $profile->user_id) }}" class="text-purple-700 font-bold text-sm">Revisar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Actividad semanal --}}
    <section aria-labelledby="adm-weekly" class="card card-body mt-6">
        <h2 id="adm-weekly" class="text-lg font-display font-bold mb-1">Registros de la última semana</h2>
        <p class="text-sm text-gray-500 mb-5">Nuevas cuentas creadas por día. La tabla debajo del gráfico muestra los mismos datos.</p>

        @php $maxSignup = max(1, $weeklySignups->max('total')); @endphp
        <div class="flex items-end justify-between gap-2 h-40" role="img" aria-label="Gráfico de barras: nuevas cuentas por día en la última semana">
            @foreach ($weeklySignups as $i => $day)
                <div class="flex-1 flex flex-col items-center gap-2">
                    <div class="w-full flex items-end justify-center h-28">
                        <div class="grow-bar w-8 sm:w-10 rounded-t-lg" style="background: var(--color-primary); height: {{ max(6, round(($day['total'] / $maxSignup) * 100)) }}%; animation-delay: {{ $i * 0.06 }}s"></div>
                    </div>
                    <p class="mono-label !text-[10px]">{{ $day['label'] }}</p>
                </div>
            @endforeach
        </div>

        <table class="table mt-6">
            <caption class="sr-only">Tabla equivalente al gráfico: nuevas cuentas registradas por día</caption>
            <thead>
                <tr>
                    @foreach ($weeklySignups as $day)<th>{{ $day['date'] }}</th>@endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    @foreach ($weeklySignups as $day)<td class="font-bold">{{ $day['total'] }}</td>@endforeach
                </tr>
            </tbody>
        </table>
    </section>

    {{-- Auditoría --}}
    <section aria-labelledby="adm-audit" class="card card-body mt-6">
        <div class="flex items-center justify-between mb-4">
            <h2 id="adm-audit" class="text-lg font-display font-bold !mb-0">Línea de tiempo de auditoría</h2>
            <a href="{{ route('admin.audit.index') }}" class="link-all">Ver todo <x-icon name="arrow-right" class="w-4 h-4" /></a>
        </div>
        @if ($recentAuditLogs->isEmpty())
            <p class="text-gray-500 text-sm">Sin actividad registrada aún.</p>
        @else
            <ol class="relative border-l-[1.5px] pl-5 space-y-5" style="border-color: var(--color-border-2)">
                @foreach ($recentAuditLogs as $log)
                    <li class="relative">
                        <span class="absolute -left-[1.65rem] top-1 w-2.5 h-2.5 rounded-full" style="background: var(--color-primary)" aria-hidden="true"></span>
                        <p class="text-sm font-bold">{{ $log->description ?? $log->action }}</p>
                        <p class="mono-label mt-1">
                            {{ $log->user_name ?? $log->user?->name ?? 'Sistema' }} · {{ $log->created_at->format('d/m/Y H:i') }}
                            @if ($log->ip_address) · IP {{ $log->ip_address }} @endif
                        </p>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
@endsection