@extends('layouts.panel-entrepreneur')

@section('panel-content')
    <x-panel-header title="Productos y servicios" subtitle="Publica lo que vendes; los administradores lo revisan antes de mostrarlo al público.">
        @slot('actions')
            <a href="{{ route('entrepreneur.publications.create') }}" class="btn btn-primary">+ Nueva publicación</a>
        @endslot
    </x-panel-header>

    @if ($publications->isEmpty())
        <div class="empty-state" role="status">
            <span class="empty-icon"><x-icon name="box" /></span>
            <p class="mt-3 font-semibold text-gray-800">Aún no tienes publicaciones</p>
            <p class="text-gray-500 text-sm mt-1">Crea una publicación, genera su flyer con IA y solicita su aprobación.</p>
        </div>
    @else
        <div class="card overflow-hidden">
            <table class="table table-auto">
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Emprendimiento</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Precio</th>
                        <th scope="col">Flyer</th>
                        <th scope="col">Estado</th>
                        <th scope="col"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($publications as $pub)
                        <tr>
                            <td class="font-medium">{{ $pub->name }}</td>
                            <td>{{ $pub->business?->name }}</td>
                            <td>{{ ucfirst($pub->type) }}</td>
                            <td>{{ $pub->price_display }}</td>
                            <td>
                                @if ($pub->flyer_image)
                                    <img src="{{ asset($pub->flyer_image) }}" alt="Flyer de {{ $pub->name }}" class="h-10 w-14 object-cover rounded">
                                @else
                                    <span class="text-xs text-gray-400">Sin flyer</span>
                                @endif
                            </td>
                            <td><x-status-badge :status="$pub->status" /></td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('entrepreneur.publications.edit', $pub) }}" class="btn btn-sm btn-secondary">Editar</a>
                                <a href="{{ route('entrepreneur.flyers.index', $pub) }}" class="btn btn-sm btn-outline">Flyer IA</a>
                                @if ($pub->status === 'publicada')
                                    <a href="{{ route('public.publication', $pub->slug) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline">Ver</a>
                                @endif
                                @if ($pub->status === 'rechazada' && $pub->rejection_reason)
                                    <span class="block mt-1 text-xs text-red-700" title="{{ $pub->rejection_reason }}">⚠ Motivo: {{ Str::limit($pub->rejection_reason, 60) }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($publications->contains(fn ($p) => $p->status === 'pendiente'))
            <div role="status" class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                Tienes publicaciones en revisión. El administrador las revisará pronto.
            </div>
        @endif
    @endif
@endsection