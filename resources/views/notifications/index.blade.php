@extends('layouts.app')

@php
    $isAdmin = auth()->user()->isAdmin();
    $readRoute = $isAdmin ? 'admin.notifications.read' : (auth()->user()->isEntrepreneur() ? 'entrepreneur.notifications.read' : null);
@endphp

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <x-panel-header title="Notificaciones" subtitle="Mantente al tanto de lo que ocurre en SONARA." />

    @if ($notifications->isEmpty())
        <div class="empty-state" role="status">
            <span class="empty-icon"><x-icon name="bell" /></span>
            <p class="mt-3 font-semibold text-gray-800">No tienes notificaciones</p>
            <p class="text-gray-500 text-sm mt-1">Aquí verás los avisos de tus solicitudes, publicaciones y verificaciones.</p>
        </div>
    @else
        <ul class="space-y-3" role="list">
            @foreach ($notifications as $notification)
                @php $data = $notification->data; @endphp
                <li>
                    <div class="card card-body flex flex-col sm:flex-row sm:items-center justify-between gap-3 {{ $notification->read_at ? '' : '!border-l-4 !border-l-purple-700' }}">
                        <div class="min-w-0">
                            <p class="font-medium {{ $notification->read_at ? 'text-gray-700' : 'text-gray-900' }}">{{ $data['title'] ?? 'Notificación' }}</p>
                            <p class="text-sm text-gray-600 mt-0.5">{{ $data['message'] ?? '' }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        @if ($readRoute)
                            <form method="POST" action="{{ route($readRoute, $notification) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline">
                                    Abrir <x-icon name="arrow-right" class="w-3.5 h-3.5" />
                                </button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection