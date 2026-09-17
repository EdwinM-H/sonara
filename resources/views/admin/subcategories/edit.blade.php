@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header title="Editar subcategoría" />
    <div class="card card-body max-w-xl">
        @include('admin.subcategories._form', ['subcategory' => $subcategory])
    </div>
@endsection