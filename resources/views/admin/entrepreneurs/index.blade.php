@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header title="Emprendedores" subtitle="Gestiona la verificación y el estado de los emprendedores.">
        @slot('actions')
            <a href="{{ route('admin.entrepreneurs.create') }}" class="btn btn-primary">+ Nuevo emprendedor</a>
            <a href="{{ route('admin.assisted.index') }}" class="btn btn-secondary"><x-icon name="chat" class="w-4 h-4" /> Registro asistido</a>
        @endslot
    </x-panel-header>

    {{-- El PIN nunca se muestra: solo si está configurado (hasheado). --}}
    <div class="card overflow-x-auto">
        <table class="table table-auto">
            <thead>
                <tr>
                    <th scope="col">Nombres</th>
                    <th scope="col">Apellidos</th>
                    <th scope="col">Sobre mí</th>
                    <th scope="col">Ubicación</th>
                    <th scope="col">WhatsApp</th>
                    <th scope="col">PIN</th>
                    <th scope="col">Datos</th>
                    <th scope="col">Verificación</th>
                    <th scope="col">Estado</th>
                    <th scope="col"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entrepreneurs as $user)
                    @php
                        $issues = $validator->issuesFor($user);
                        $pinStatus = $validator->pinStatus($user);
                    @endphp
                    <tr>
                        <td>
                            <p class="font-medium">{{ $user->first_name ?: '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $user->username ? 'Usuario: '.$user->username : 'Sin usuario de voz' }}</p>
                        </td>
                        <td>{{ $user->last_name ?: '—' }}</td>
                        <td class="max-w-[16rem]"><span class="line-clamp-2">{{ $user->entrepreneurProfile?->personal_description ?: '—' }}</span></td>
                        <td>{{ $user->entrepreneurProfile?->location ?: '—' }}</td>
                        <td class="whitespace-nowrap">{{ $user->phone ?: '—' }}</td>
                        <td>
                            <span class="badge {{ $pinStatus === 'hasheado' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $pinStatus === 'hasheado' ? 'Configurado' : ($pinStatus === 'falta' ? 'Falta' : 'Inseguro') }}
                            </span>
                        </td>
                        <td>
                            @if ($issues)
                                <span class="badge bg-purple-100 text-purple-800"
                                      title="{{ collect($issues)->map(fn ($p, $f) => \App\Services\Verification\EntrepreneurRecordValidator::FIELDS[$f].': '.$p)->implode(', ') }}">
                                    Incompleto ({{ count($issues) }})
                                </span>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ collect(array_keys($issues))->map(fn ($f) => \App\Services\Verification\EntrepreneurRecordValidator::FIELDS[$f])->implode(', ') }}
                                </p>
                            @else
                                <span class="badge bg-green-100 text-green-800">Completo</span>
                            @endif
                        </td>
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
