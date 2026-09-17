@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header title="Revisión de publicaciones" subtitle="Las publicaciones pasan por tu revisión antes de mostrarse al público." />

    @php
        $counts = [
            'pendiente' => \App\Models\Publication::where('status', 'pendiente')->count(),
            'publicada' => \App\Models\Publication::where('status', 'publicada')->count(),
            'rechazada' => \App\Models\Publication::where('status', 'rechazada')->count(),
        ];
    @endphp

    <div class="flex flex-wrap gap-2 mb-6 text-sm">
        <span class="badge bg-amber-100 text-amber-800">Pendientes: {{ $counts['pendiente'] }}</span>
        <span class="badge bg-green-100 text-green-800">Publicadas: {{ $counts['publicada'] }}</span>
        <span class="badge bg-red-100 text-red-800">Rechazadas: {{ $counts['rechazada'] }}</span>
    </div>

    <div class="card overflow-hidden">
        <table class="table table-auto">
            <thead>
                <tr>
                    <th scope="col">Nombre</th>
                    <th scope="col">Emprendimiento</th>
                    <th scope="col">Emprendedor</th>
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
                        <td>{{ $pub->business?->entrepreneurProfile?->user?->name ?? '—' }}</td>
                        <td>
                            @if ($pub->flyer_image)
                                <img src="{{ asset($pub->flyer_image) }}" alt="Flyer" class="h-8 w-12 object-cover rounded">
                            @else
                                <span class="text-xs text-gray-400">Sin flyer</span>
                            @endif
                        </td>
                        <td><x-status-badge :status="$pub->status" /></td>
                        <td><a href="{{ route('admin.publications.show', $pub) }}" class="btn btn-sm btn-outline">Revisar</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $publications->links() }}</div>
@endsection