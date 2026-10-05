@extends('layouts.app')

@section('title', 'Edit Employee')

@section('content')
    <h1 class="h3 mb-3">Edit Employee: {{ $employee->full_name }}</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('employees.update', $employee) }}" method="POST">
                @method('PUT')
                @include('employees._form', ['submitLabel' => 'Update'])
            </form>
        </div>
    </div>
@endsection
