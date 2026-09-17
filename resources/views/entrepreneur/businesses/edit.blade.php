@extends('layouts.panel-entrepreneur')

@section('panel-content')
    <x-panel-header title="Editar emprendimiento" :subtitle="$business->name">
        @slot('actions')
            <div class="flex items-center gap-2">
                <a href="{{ route('public.business', $business->slug) }}" target="_blank" rel="noopener" class="btn btn-outline">Ver página pública</a>
            </div>
        @endslot
    </x-panel-header>

    <div class="card card-body max-w-3xl">
        <form method="POST" action="{{ route('entrepreneur.businesses.update', $business) }}">
            @csrf
            @method('PATCH')
            @include('entrepreneur.businesses._form', [
                'business' => $business,
                'categories' => $categories,
                'subcategories' => $subcategories,
                'days' => $days,
            ])
        </form>
    </div>
@endsection