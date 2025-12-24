@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="mb-3">Dashboard Admin</h1>
    <p>Selamat datang, <strong>{{ auth()->user()->name }}</strong>! Anda login sebagai <b>Admin</b>.</p>

    <a href="{{ route('programkerja.index') }}" class="btn btn-primary mt-3">Lihat Program Kerja</a>
    <form action="{{ route('logout') }}" method="POST" class="mt-2">
        @csrf
        <button type="submit" class="btn btn-danger">Logout</button>
    </form>
</div>
@endsection
