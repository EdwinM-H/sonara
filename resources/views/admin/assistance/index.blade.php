@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header title="Solicitudes de asistencia" subtitle="Ayuda a los usuarios que necesitan soporte." />

    @if ($assistanceRequests->isEmpty())
        <div class="empty-state" role="status">
            <span class="empty-icon"><x-icon name="chat" /></span>
            <p class="mt-3 font-semibold text-gray-800">No hay solicitudes de asistencia</p>
            <p class="text-gray-500 text-sm mt-1">Los usuarios pedirán ayuda aquí cuando la necesiten.</p>
        </div>
    @else
        <div class="card overflow-hidden">
            <table class="table table-auto">
                <thead>
                    <tr>
                        <th scope="col">Usuario</th>
                        <th scope="col">Asunto</th>
                        <th scope="col">Canal</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Fecha</th>
                        <th scope="col"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($assistanceRequests as $a)
                        <tr>
                            <td class="font-medium">{{ $a->user?->name }}</td>
                            <td>{{ Str::limit($a->subject, 50) }}</td>
                            <td>{{ $a->preferred_channel }}</td>
                            <td><x-status-badge :status="$a->status" /></td>
                            <td>{{ $a->created_at->format('d/m/Y') }}</td>
                            <td><a href="{{ route('admin.assistance.show', $a) }}" class="btn btn-sm btn-outline">Atender</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $assistanceRequests->links() }}</div>
    @endif
@endsection