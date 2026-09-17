@extends('layouts.panel-entrepreneur')

@section('panel-content')
    <x-panel-header title="Nuevo emprendimiento" subtitle="Cuéntale al público de qué trata tu tienda." />

    <div class="card card-body max-w-3xl">
        <form method="POST" action="{{ route('entrepreneur.businesses.store') }}">
            @csrf
            @include('entrepreneur.businesses._form', ['categories' => $categories, 'days' => $days])
        </form>
    </div>
@endsection