@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header :title="$business->name" subtitle="Detalle del emprendimiento." />

    <div class="card card-body mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <x-status-badge :status="$business->status" />
                <span class="text-sm text-gray-600">Verificado: {{ $business->entrepreneurProfile?->isVerified() ? '✓ Sí' : 'No' }}</span>
            </div>
            <form method="POST" action="{{ route('admin.businesses.toggleStatus', $business) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $business->status === 'activo' ? 'inactivo' : 'activo' }}">
                <button type="submit" class="btn {{ $business->status === 'activo' ? 'btn-danger' : 'btn-success' }}">
                    {{ $business->status === 'activo' ? 'Desactivar' : 'Activar' }}
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card card-body">
            <h2 class="text-lg font-bold mb-3">Información</h2>
            <dl class="text-sm space-y-2">
                <div><dt class="font-semibold text-gray-500">Emprendedor</dt><dd>{{ $business->entrepreneurProfile?->user?->name ?? '—' }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Categoría / Subcategoría</dt><dd>{{ $business->category?->name ?? '—' }} / {{ $business->subcategory?->name ?? '—' }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Tipo</dt><dd>{{ ucfirst($business->type) }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Descripción</dt><dd>{{ $business->description ?? '—' }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Ubicación</dt><dd>{{ $business->location_summary }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Horario</dt><dd>{{ $business->schedule_summary }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Precio</dt><dd>{{ $business->display_price }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Contacto</dt>
                    <dd>
                        @if ($business->phone) <p class="flex items-center gap-1.5"><x-icon name="phone" class="w-4 h-4 text-purple-500 shrink-0" /> {{ $business->phone }}</p> @endif
                        @if ($business->whatsapp) <p class="flex items-center gap-1.5"><x-icon name="chat" class="w-4 h-4 text-purple-500 shrink-0" /> {{ $business->whatsapp }}</p> @endif
                        @if ($business->contact_email) <p class="flex items-center gap-1.5"><x-icon name="user" class="w-4 h-4 text-purple-500 shrink-0" /> {{ $business->contact_email }}</p> @endif
                    </dd>
                </div>
            </dl>
        </div>

        <div class="card card-body">
            <h2 class="text-lg font-bold mb-3">Publicaciones ({{ $business->publications->count() }})</h2>
            @if ($business->publications->isEmpty())
                <p class="text-gray-500 text-sm">Sin publicaciones.</p>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($business->publications as $pub)
                        <li class="py-2 flex items-center justify-between gap-2">
                            <span class="font-medium">{{ $pub->name }}</span>
                            <x-status-badge :status="$pub->status" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection