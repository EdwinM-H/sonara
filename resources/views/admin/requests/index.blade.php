@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header title="Solicitudes de clientes" subtitle="Todas las solicitudes enviadas por clientes a los emprendedores." />

    <div class="card overflow-hidden">
        <table class="table table-auto">
            <thead>
                <tr>
                    <th scope="col">Código</th>
                    <th scope="col">Cliente</th>
                    <th scope="col">Emprendimiento</th>
                    <th scope="col">Producto / Servicio</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Fecha</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($requests as $req)
                    <tr>
                        <td class="font-mono text-sm">{{ $req->code }}</td>
                        <td>{{ $req->customer_name }}</td>
                        <td>{{ $req->business?->name }}</td>
                        <td>{{ $req->publication?->name ?? $req->item_name }}</td>
                        <td><x-status-badge :status="$req->status" /></td>
                        <td>{{ $req->created_at->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $requests->links() }}</div>
@endsection