@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Dashboard User</h1>
    <p>Selamat datang, {{ auth()->user()->name }}!</p>
    <p>Anda login sebagai <strong>{{ auth()->user()->role }}</strong>.</p>

    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-danger">Logout</button>
    </form>
</div>
@endsection
