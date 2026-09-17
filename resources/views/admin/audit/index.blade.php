@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header title="Auditoría" subtitle="Registro de las acciones realizadas por usuarios y administradores." />

    <div class="card overflow-hidden">
        <table class="table table-auto">
            <thead>
                <tr>
                    <th scope="col">Fecha</th>
                    <th scope="col">Usuario</th>
                    <th scope="col">Acción</th>
                    <th scope="col">Entidad</th>
                    <th scope="col">Detalle</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap text-sm">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $log->user?->name ?? 'Sistema' }}</td>
                        <td><span class="badge bg-purple-100 text-purple-800">{{ $log->action }}</span></td>
                        <td class="text-sm">{{ $log->auditable_type ? class_basename($log->auditable_type) : '—' }}</td>
                        <td class="text-sm text-gray-600">{{ Str::limit($log->details, 100) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $logs->links() }}</div>
@endsection