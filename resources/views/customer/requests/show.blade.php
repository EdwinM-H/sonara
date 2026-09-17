@extends('layouts.panel-customer')

@section('panel-content')
    <x-panel-header :title="'Solicitud '.$request->code" subtitle="Verifica el estado de tu pedido y el detalle enviado al emprendedor." />

    <div class="card card-body max-w-2xl space-y-4">
        <x-status-badge :status="$request->status" />

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div><dt class="font-semibold text-gray-500">Producto / Servicio</dt><dd class="mt-0.5">{{ $request->publication?->name ?? $request->item_name }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Emprendimiento</dt><dd class="mt-0.5">{{ $request->business?->name }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Cantidad</dt><dd class="mt-0.5">{{ $request->quantity }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Fecha preferida</dt>
                <dd class="mt-0.5">@if ($request->preferred_at) {{ \Illuminate\Support\Carbon::parse($request->preferred_at)->format('d/m/Y H:i') }} @else — @endif</dd>
            </div>
            <div><dt class="font-semibold text-gray-500">Tu mensaje</dt><dd class="mt-0.5">{{ $request->message ?? '—' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Enviada el</dt><dd class="mt-0.5">{{ $request->created_at->format('d/m/Y H:i') }}</dd></div>
            @if ($request->response_note)
                <div class="sm:col-span-2 rounded-lg bg-purple-50 border border-purple-200 p-3">
                    <dt class="font-semibold text-purple-800">Respuesta del emprendedor</dt>
                    <dd class="mt-1">{{ $request->response_note }}</dd>
                </div>
            @endif
        </dl>

        @if ($request->business)
            <a href="{{ route('public.business', $request->business->slug) }}" class="btn btn-secondary">Ver emprendimiento</a>
        @endif

        @if ($request->canTransitionTo(\App\Models\Request::STATUS_CANCELADA))
            <form method="POST" action="{{ route('customer.requests.cancel', $request) }}" class="pt-2 border-t border-gray-100"
                  onsubmit="return confirm('¿Estás seguro de cancelar esta solicitud?');">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-danger">Cancelar solicitud</button>
            </form>
        @endif
    </div>
@endsection