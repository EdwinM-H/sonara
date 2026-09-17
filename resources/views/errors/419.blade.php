@extends('layouts.app')

@section('title', 'Sesión expirada')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
    <p class="text-7xl font-black text-amber-600" aria-hidden="true">419</p>
    <h1 class="text-2xl font-black mt-4">Tu sesión expiró</h1>
    <p class="text-gray-600 mt-2">Vuelve a intentar la acción; tu sesión ya no es válida.</p>
    <a href="{{ url()->previous() ?: route('public.home') }}" class="btn btn-primary mt-6">Intentar de nuevo</a>
</div>
@endsection