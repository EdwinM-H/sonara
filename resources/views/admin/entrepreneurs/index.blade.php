@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header title="Emprendedores" subtitle="Gestiona la verificación y el estado de los emprendedores.">
        @slot('actions')
            <a href="{{ route('admin.entrepreneurs.create') }}" class="btn btn-primary">+ Nuevo emprendedor</a>
            <a href="{{ route('admin.assisted.index') }}" class="btn btn-secondary"><x-icon name="chat" class="w-4 h-4" /> Registro asistido</a>
        @endslot
    </x-panel-header>

    <div class="card overflow-hidden">
        <table class="table table-auto">
            <thead>
                <tr>
                    <th scope="col">Emprendedor</th>
                    <th scope="col">Correo</th>
                    <th scope="col">Emprendimientos</th>
                    <th scope="col">Verificación</th>
                    <th scope="col">Estado</th>
                    <th scope="col"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entrepreneurs as $user)
                    <tr>
                        <td>
                            <p class="font-medium">{{ $user->name }}</p>
                            <p class="text-xs text-gray-400">Registrado {{ $user->created_at->format('d/m/Y') }}</p>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->businesses->count() }}</td>
                        <td>
                            @if ($profile = $user->entrepreneurProfile)
                                <x-status-badge :status="$profile->verification_status" />
                            @else
                                —
                            @endif
                        </td>
                        <td><x-status-badge :status="$user->status" /></td>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('admin.entrepreneurs.show', $user) }}" class="btn btn-sm btn-outline">Ver</a>
                            <a href="{{ route('admin.entrepreneurs.edit', $user) }}" class="btn btn-sm btn-secondary">Editar</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $entrepreneurs->links() }}</div>
@endsection