@extends('layouts.app')

@section('title', 'Acceso denegado')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
    <p class="text-7xl font-black text-red-700" aria-hidden="true">403</p>
    <h1 class="text-2xl font-black mt-4">Acceso denegado</h1>
    <p class="text-gray-600 mt-2">No tienes permisos para ver esta página.</p>
    <a href="{{ route('public.home') }}" class="btn btn-primary mt-6">Volver al inicio</a>
</div>
@endsection