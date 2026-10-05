@extends('layouts.app')

@section('title', 'Add Employee')

@section('content')
    <h1 class="h3 mb-3">Add Employee</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('employees.store') }}" method="POST">
                @include('employees._form', ['submitLabel' => 'Create'])
            </form>
        </div>
    </div>
@endsection
