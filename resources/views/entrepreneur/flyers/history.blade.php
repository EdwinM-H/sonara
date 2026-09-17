@extends('layouts.panel-entrepreneur')

@section('panel-content')
    <x-panel-header title="Mis flyers IA" subtitle="Todas las imágenes que has generado con inteligencia artificial." />

    @if ($generations->isEmpty())
        <div class="empty-state" role="status">
            <span class="empty-icon"><x-icon name="sparkles" /></span>
            <p class="mt-3 font-semibold text-gray-800">Aún no generaste flyers</p>
            <p class="text-gray-500 text-sm mt-1">Ve a cualquiera de tus publicaciones y usa el generador de flyers.</p>
            <a href="{{ route('entrepreneur.publications.index') }}" class="btn btn-primary mt-5">Ir a mis publicaciones</a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($generations as $gen)
                <article class="card overflow-hidden card-hover flex flex-col">
                    <div class="bg-purple-50 overflow-hidden">
                        <img src="{{ asset($gen->image_path) }}" alt="Flyer generado el {{ $gen->created_at->format('d/m/Y') }}" class="w-full h-44 object-cover">
                    </div>
                    <div class="p-4 flex-1 flex flex-col">
                        <p class="font-medium truncate">{{ $gen->publication?->name ?? 'Sin publicación' }}</p>
                        <p class="text-xs text-gray-500">{{ $gen->business?->name }} · {{ ucfirst($gen->provider_name) }}</p>
                        <div class="flex items-center justify-between mt-2">
                            <p class="text-xs text-gray-400">{{ $gen->created_at->format('d/m/Y H:i') }}</p>
                            <x-status-badge :status="$gen->status" />
                        </div>
                        @if ($gen->publication?->id)
                            <a href="{{ route('entrepreneur.publications.edit', $gen->publication) }}" class="btn btn-sm btn-outline mt-3 !self-start">Ir a la publicación</a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-8">{{ $generations->links() }}</div>
    @endif
@endsection