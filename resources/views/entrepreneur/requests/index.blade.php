@extends('layouts.panel-entrepreneur')

@section('panel-content')
    <x-panel-header title="Solicitudes recibidas" subtitle="Los clientes te envían pedidos de tus productos o servicios." />

    @if ($requests->isEmpty())
        <div class="empty-state" role="status">
            <span class="empty-icon"><x-icon name="clipboard" /></span>
            <p class="mt-3 font-semibold text-gray-800">Aún no recibes solicitudes</p>
            <p class="text-gray-500 text-sm mt-1">Comparte el enlace de tu emprendimiento para recibir tus primeros pedidos.</p>
        </div>
    @else
        <div class="card overflow-hidden">
            <table class="table table-auto">
                <thead>
                    <tr>
                        <th scope="col">Código</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Producto/Servicio</th>
                        <th scope="col">Cant.</th>
                        <th scope="col">Fecha</th>
                        <th scope="col">Estado</th>
                        <th scope="col"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $req)
                        <tr>
                            <td class="font-mono text-sm">{{ $req->code }}</td>
                            <td>{{ $req->name ?? $req->customer?->name }}</td>
                            <td>{{ $req->publication?->name ?? $req->item_name }}</td>
                            <td>{{ $req->quantity }}</td>
                            <td>{{ $req->created_at->format('d/m/Y H:i') }}</td>
                            <td><x-status-badge :status="$req->status" /></td>
                            <td><a href="{{ route('entrepreneur.requests.show', $req) }}" class="btn btn-sm btn-outline">Ver</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    @endif
@endsection