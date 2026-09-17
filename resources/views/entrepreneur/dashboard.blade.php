@extends('layouts.panel-entrepreneur')

@section('panel-content')
    <x-panel-header title="Hola, {{ auth()->user()->first_name }}" subtitle="Este es el resumen de tu emprendimiento en SONARA." />

    {{-- Alertas de verificación --}}
    @if ($profile && $profile->verification_status !== \App\Models\EntrepreneurProfile::VERIF_APROBADO)
        <div role="alert" class="rounded-2xl mb-6 border-[1.5px] p-5 flex gap-4" style="background-color: var(--color-warning-soft); border-color: var(--color-warning-border); color: var(--color-warning)">
            <span class="grid place-items-center w-10 h-10 rounded-xl bg-white shrink-0" aria-hidden="true">
                <x-icon name="alert" />
            </span>
            <div>
                <p class="font-display font-bold text-lg">Tu cuenta aún no está verificada</p>
                @if ($profile->verification_status === 'rechazado')
                    <p class="mt-1">Tu documentación fue rechazada.</p>
                    @if ($profile->review_note)
                        <p class="mt-2 rounded-xl bg-white/70 border border-current/20 px-3 py-2 text-sm"><strong>Motivo:</strong> {{ $profile->review_note }}</p>
                    @endif
                    <p class="mt-2">Revisa el motivo y vuelve a cargar tu documento en <a href="{{ route('entrepreneur.documents.index') }}" class="underline font-bold">Documentación</a>.</p>
                @elseif ($profile->verification_status === 'en_revision')
                    <p class="mt-1">Tu documento está en revisión por el administrador. Sueles tener respuesta en un máximo de <strong>{{ $validationDays }} día(s)</strong>.</p>
                @else
                    @if ($profile->review_note)
                        <p class="mt-2 rounded-xl bg-white/70 border border-current/20 px-3 py-2 text-sm"><strong>El administrador pidió corregir:</strong> {{ $profile->review_note }}</p>
                    @endif
                    <p class="mt-2">Carga tu carné de acreditación dentro de los plazos establecidos para convertirte en emprendedor verificado.
                        @if ($documentDays !== null)<strong>Te quedan {{ $documentDays }} día(s).</strong>@endif
                    </p>
                @endif
                <div class="flex flex-wrap gap-3 mt-3">
                    @if (in_array($profile->verification_status, ['pendiente_documento', 'rechazado'], true))
                        <a href="{{ route('entrepreneur.documents.index') }}" class="btn btn-primary btn-sm">Cargar documento</a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Estadísticas --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <x-stat-card label="Emprendimientos" :value="$businesses->count()" icon="tag" />
        <x-stat-card label="Publicados" :value="$businesses->sum('published_count')" icon="box" />
        <x-stat-card label="Solicitudes recientes" :value="$requests->count()" icon="clipboard" />
        <x-stat-card label="Asistencias" :value="$assistance->count()" icon="chat" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Últimas solicitudes --}}
        <section aria-labelledby="ent-last-requests" class="card card-body">
            <div class="flex items-center justify-between mb-4">
                <h2 id="ent-last-requests" class="text-lg font-display font-bold !mb-0">Últimas solicitudes</h2>
                <a href="{{ route('entrepreneur.requests.index') }}" class="link-all">Ver todas <x-icon name="arrow-right" class="w-4 h-4" /></a>
            </div>
            @if ($requests->isEmpty())
                <p class="text-gray-500 text-sm">Aún no recibes solicitudes. Explora el portal para compartir tu emprendimiento.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($requests as $req)
                        <li class="flex items-center justify-between gap-3 rounded-xl border-[1.5px] p-3 hover:border-purple-300 transition-colors" style="border-color: var(--color-border)">
                            <div class="min-w-0">
                                <p class="font-bold truncate">{{ $req->name ?? 'Solicitud' }}</p>
                                <p class="text-xs" style="color: var(--color-muted)">{{ $req->publication?->name ?? $req->business?->name }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <x-status-badge :status="$req->status" />
                                <a href="{{ route('entrepreneur.requests.show', $req) }}" class="inline-flex items-center gap-1 text-purple-700 text-sm font-bold">Ver <x-icon name="arrow-right" class="w-3.5 h-3.5" /></a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Acciones rápidas --}}
        <section aria-labelledby="ent-quick-actions" class="card card-body">
            <h2 id="ent-quick-actions" class="text-lg font-display font-bold !mb-0 mb-4">Acciones rápidas</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                <a href="{{ route('entrepreneur.businesses.create') }}" class="btn btn-primary justify-start">
                    <x-icon name="plus" class="w-4 h-4" /> Nuevo emprendimiento
                </a>
                <a href="{{ route('entrepreneur.publications.create') }}" class="btn btn-secondary justify-start">
                    <x-icon name="plus" class="w-4 h-4" /> Nueva publicación
                </a>
                <a href="{{ route('entrepreneur.documents.index') }}" class="btn btn-secondary justify-start">
                    <x-icon name="document" class="w-4 h-4" /> Documentación
                </a>
                <a href="{{ route('entrepreneur.assistance.index') }}" class="btn btn-secondary justify-start">
                    <x-icon name="chat" class="w-4 h-4" /> Pedir ayuda
                </a>
            </div>

            @if ($businesses->isNotEmpty())
                <h2 class="text-lg font-display font-bold !mb-0 mt-8 mb-3">Mis emprendimientos</h2>
                <ul class="space-y-2">
                    @foreach ($businesses as $biz)
                        <li class="flex items-center justify-between rounded-xl border-[1.5px] p-3 hover:border-purple-300 transition-colors" style="border-color: var(--color-border)">
                            <div class="min-w-0">
                                <p class="font-bold truncate">{{ $biz->name }}</p>
                                <p class="text-xs" style="color: var(--color-muted)">{{ $biz->published_count }} publicado(s)</p>
                            </div>
                            <a href="{{ route('entrepreneur.businesses.edit', $biz) }}" class="text-purple-700 text-sm font-bold shrink-0">Editar</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    {{-- Generador de flyers IA --}}
    <section aria-labelledby="ent-ai-flyers" class="relative overflow-hidden rounded-[1.5rem] text-white mt-6 p-6 sm:p-8" style="background-color: var(--color-ink)">
        <div class="absolute -top-12 -right-12 w-56 h-56 rounded-full opacity-25 blur-3xl pointer-events-none" style="background: var(--color-primary)"></div>
        <div class="relative flex flex-col sm:flex-row sm:items-center gap-6 justify-between">
            <div class="flex items-center gap-4">
                <span class="grid place-items-center w-14 h-14 rounded-2xl shrink-0" style="background: var(--color-accent); color: var(--color-ink)" aria-hidden="true">
                    <x-icon name="sparkles" class="w-7 h-7" />
                </span>
                <div>
                    <h2 id="ent-ai-flyers" class="font-display font-bold text-xl">Generador de flyers con IA</h2>
                    <p class="text-white/60 text-sm mt-0.5">Crea afiches profesionales para tus publicaciones en segundos.</p>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div class="text-right">
                    <p class="mono-label !text-white/50">Uso este período</p>
                    <p class="font-display font-extrabold text-2xl"><span style="color: var(--color-accent)">{{ $aiRemaining }}</span> <span class="text-white/40 text-base font-sans font-normal">/ {{ $aiLimit }} disponibles</span></p>
                </div>
                <a href="{{ route('entrepreneur.flyers.history') }}" class="btn btn-dark shrink-0">
                    <x-icon name="sparkles" class="w-4 h-4" /> Ver mis flyers
                </a>
            </div>
        </div>

        <div class="relative mt-5 h-1.5 rounded-full bg-white/10 overflow-hidden max-w-md">
            <div class="h-full rounded-full" style="background: var(--color-accent); width: {{ $aiLimit > 0 ? min(100, round((($aiLimit - $aiRemaining) / $aiLimit) * 100)) : 0 }}%"></div>
        </div>
    </section>
@endsection
