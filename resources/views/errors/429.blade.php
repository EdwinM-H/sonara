@extends('layouts.app')

@section('title', 'Demasiadas solicitudes')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
    <p class="text-7xl font-black text-orange-600" aria-hidden="true">429</p>
    <h1 class="text-2xl font-black mt-4">Demasiadas solicitudes</h1>
    <p class="text-gray-600 mt-2">Has superado el límite de solicitudes. Espera un momento y vuelve a intentar.</p>
    <a href="{{ url()->previous() ?: route('public.home') }}" class="btn btn-primary mt-6">Volver</a>
</div>
@endsection