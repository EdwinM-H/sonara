@extends('layouts.panel-entrepreneur')

@section('panel-content')
    <x-panel-header :title="'Solicitud '.$request->code" subtitle="Revisa los datos y responde al cliente." />

    <div class="card card-body max-w-2xl space-y-4">
        <div class="flex items-center justify-between">
            <x-status-badge :status="$request->status" />
            <a href="tel:{{ $request->customer_phone }}" class="btn btn-sm btn-secondary"><x-icon name="phone" class="w-4 h-4" /> Llamar al cliente</a>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div><dt class="font-semibold text-gray-500">Cliente</dt><dd class="mt-0.5">{{ $request->customer_name }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Teléfono</dt><dd class="mt-0.5">{{ $request->customer_phone }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Producto / Servicio</dt><dd class="mt-0.5">{{ $request->publication?->name ?? $request->item_name }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Emprendimiento</dt><dd class="mt-0.5">{{ $request->business?->name }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Cantidad</dt><dd class="mt-0.5">{{ $request->quantity }}</dd></div>
            <div>
                <dt class="font-semibold text-gray-500">Fecha / hora preferida</dt>
                <dd class="mt-0.5">
                    @if ($request->preferred_at)
                        {{ \Illuminate\Support\Carbon::parse($request->preferred_at)->format('d/m/Y H:i') }}
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div class="sm:col-span-2"><dt class="font-semibold text-gray-500">Mensaje</dt><dd class="mt-0.5">{{ $request->message ?? '—' }}</dd></div>
        </dl>

        @if ($request->response_note)
            <div class="rounded-xl bg-purple-50 border border-purple-200 p-4 text-sm">
                <p class="font-semibold text-purple-800">Nota registrada</p>
                <p class="mt-1">{{ $request->response_note }}</p>
            </div>
        @endif

        @if (in_array($request->status, ['enviada', 'vista'], true))
            <div class="flex flex-wrap gap-3 pt-2 border-t border-gray-100">
                <form method="POST" action="{{ route('entrepreneur.requests.accept', $request) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">✓ Aceptar solicitud</button>
                </form>
                <form method="POST" action="{{ route('entrepreneur.requests.reject', $request) }}" class="flex flex-wrap items-start gap-2">
                    @csrf
                    @method('PATCH')
                    <label class="sr-only" for="reason">Motivo del rechazo (opcional)</label>
                    <input type="text" id="reason" name="response_note" placeholder="Motivo (opcional)" class="input-text !py-1.5 text-sm w-56">
                    <button type="submit" class="btn btn-danger">✕ Rechazar</button>
                </form>
            </div>
        @endif

        @if ($request->canTransitionTo(\App\Models\Request::STATUS_COMPLETADA))
            <form method="POST" action="{{ route('entrepreneur.requests.complete', $request) }}" class="pt-2 border-t border-gray-100">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-primary">✓ Marcar como completada</button>
            </form>
        @endif
    </div>
@endsection