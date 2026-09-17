@extends('layouts.app')

@section('title', 'Página no encontrada')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
    <p class="text-7xl font-black text-purple-800" aria-hidden="true">404</p>
    <h1 class="text-2xl font-black mt-4">Página no encontrada</h1>
    <p class="text-gray-600 mt-2">La página que buscas no existe o fue movida de lugar.</p>
    <a href="{{ route('public.home') }}" class="btn btn-primary mt-6">Volver al inicio</a>
</div>
@endsection