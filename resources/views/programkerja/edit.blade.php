@extends('layouts.app') {{-- kalau kamu sudah punya layouts, sesuaikan nama layoutnya --}}

@section('content')
<div class="container">
    <h2 class="mb-4">Edit Program Kerja</h2>

    {{-- Tampilkan error validasi --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Periksa inputan!</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('program-kerja.update', $program->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="deskripsi" class="form-label">Deskripsi</label>
            <input type="text" class="form-control" id="deskripsi" name="deskripsi"
                   value="{{ old('deskripsi', $program->deskripsi) }}" required>
        </div>

        <div class="mb-3">
            <label for="deskripsi_lengkap" class="form-label">Deskripsi Lengkap</label>
            <textarea class="form-control" id="deskripsi_lengkap" name="deskripsi_lengkap" rows="3">{{ old('deskripsi_lengkap', $program->deskripsi_lengkap) }}</textarea>
        </div>

        <div class="mb-3">
            <label for="penanggung_jawab" class="form-label">Penanggung Jawab</label>
            <input type="text" class="form-control" id="penanggung_jawab" name="penanggung_jawab"
                   value="{{ old('penanggung_jawab', $program->penanggung_jawab) }}" required>
        </div>

        <div class="mb-3">
            <label for="waktu_dimulai" class="form-label">Waktu Dimulai</label>
            <input type="date" class="form-control" id="waktu_dimulai" name="waktu_dimulai"
                   value="{{ old('waktu_dimulai', \Carbon\Carbon::parse($program->waktu_dimulai)->format('Y-m-d')) }}" required>
        </div>

        <div class="mb-3">
            <label for="target_waktu" class="form-label">Target Waktu</label>
            <input type="date" class="form-control" id="target_waktu" name="target_waktu"
                   value="{{ old('target_waktu', \Carbon\Carbon::parse($program->target_waktu)->format('Y-m-d')) }}" required>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
                <option value="Proses" {{ old('status', $program->status) == 'Proses' ? 'selected' : '' }}>Proses</option>
                <option value="Selesai" {{ old('status', $program->status) == 'Selesai' ? 'selected' : '' }}>Selesai</option>
                <option value="Tertunda" {{ old('status', $program->status) == 'Tertunda' ? 'selected' : '' }}>Tertunda</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="kategori" class="form-label">Kategori</label>
            <input type="text" class="form-control" id="kategori" name="kategori"
                   value="{{ old('kategori', $program->kategori) }}" required>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Batal</a>
    </form>
</div>
@endsection
