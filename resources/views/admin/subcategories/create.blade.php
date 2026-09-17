@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header title="Nueva subcategoría" />
    <div class="card card-body max-w-xl">
        @include('admin.subcategories._form', ['subcategory' => null])
    </div>
@endsection