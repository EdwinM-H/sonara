@extends('layouts.panel-customer')

@section('panel-content')
    <x-panel-header title="Mis solicitudes" subtitle="Sigue el estado de tus pedidos a los emprendedores." />

    @if ($requests->isEmpty())
        <div class="card card-body text-center py-10">
            <p class="text-4xl mb-3"><x-icon name="clipboard" class="w-12 h-12 text-purple-300" /></p>
            <p class="font-semibold">No tienes solicitudes</p>
            <p class="text-gray-500 text-sm mt-1">Explora el catálogo y envía tu primera solicitud.</p>
            <a href="{{ route('public.explore') }}" class="btn btn-primary mt-4">Explorar catálogo</a>
        </div>
    @else
        <div class="card overflow-hidden">
            <table class="table table-auto">
                <thead>
                    <tr>
                        <th scope="col">Código</th>
                        <th scope="col">Producto / Servicio</th>
                        <th scope="col">Emprendimiento</th>
                        <th scope="col">Cant.</th>
                        <th scope="col">Enviada</th>
                        <th scope="col">Estado</th>
                        <th scope="col"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $req)
                        <tr>
                            <td class="font-mono text-sm">{{ $req->code }}</td>
                            <td class="font-medium">{{ $req->publication?->name ?? $req->item_name }}</td>
                            <td>{{ $req->business?->name }}</td>
                            <td>{{ $req->quantity }}</td>
                            <td>{{ $req->created_at->format('d/m/Y') }}</td>
                            <td><x-status-badge :status="$req->status" /></td>
                            <td><a href="{{ route('customer.requests.show', $req) }}" class="btn btn-sm btn-outline">Ver</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $requests->links() }}</div>
    @endif
@endsection