@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header title="Usuarios" subtitle="Todos los usuarios registrados en el sistema." />

    <div class="card overflow-hidden">
        <table class="table table-auto">
            <thead>
                <tr>
                    <th scope="col">Nombre</th>
                    <th scope="col">Correo</th>
                    <th scope="col">Teléfono</th>
                    <th scope="col">Rol</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Registrado</th>
                    <th scope="col"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td class="font-medium">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->phone ?? '—' }}</td>
                        <td>
                            @foreach ($user->roles as $role)
                                <span class="badge bg-purple-100 text-purple-800">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td><x-status-badge :status="$user->status" /></td>
                        <td>{{ $user->created_at->format('d/m/Y') }}</td>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline">Editar</a>
                            @if ($user->id !== auth()->id())
                                @if ($user->status === \App\Models\User::STATUS_ACTIVO)
                                    <form method="POST" action="{{ route('admin.users.suspend', $user) }}" class="inline" onsubmit="return confirm('¿Suspender a {{ $user->name }}?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-danger">Suspender</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.users.reactivate', $user) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-success">Reactivar</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $users->links() }}</div>
@endsection