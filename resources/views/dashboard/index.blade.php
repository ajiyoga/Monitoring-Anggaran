<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Monitoring Anggaran</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #f5f6fa;
            font-family: 'Inter', sans-serif;
        }

        .content {
            padding: 30px 60px;
        }

        .content h3 {
            font-weight: 600;
        }

        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .card h6 {
            font-weight: 600;
            margin-bottom: 15px;
        }

        .table th,
        .table td {
            vertical-align: middle;
            font-size: 14px;
        }

        .form-control {
            font-size: 14px;
        }

        canvas {
            max-height: 300px;
        }

        .card-chart {
            height: 380px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 20px;
        }

        .card-chart canvas {
            flex-grow: 1;
        }
    </style>
</head>

<body>

    {{-- Role-based controls added by automated patch --}}
    @php $role = auth()->check() ? auth()->user()->role : null; @endphp

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var role = "{{ $role }}";
            if (role !== 'admin') {
                document.querySelectorAll('.admin-only').forEach(function (el) { el.style.display = 'none'; });
            }
            if (role !== 'manajer') {
                document.querySelectorAll('.manajer-only').forEach(function (el) { el.style.display = 'none'; });
            }
        });
    </script>

    <!-- Bootstrap Bundle JS (sudah termasuk Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <div @class(['container-fluid'])>
        <div @class(['row'])>
            <!-- Content -->
            <div @class(['col-12', 'content'])>
                <!-- Dashboard Section -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="m-0">Dashboard</h3>
                </div>

                <!-- Filter Persen (Admin, Manajer, User) -->
                @if(in_array(auth()->user()->role, ['admin', 'manajer', 'user']))
                    <div class="mb-3 d-flex align-items-center">
                        <label for="filterPersen" class="me-2 fw-bold" style="width: 180px;">Tampilkan Anggaran :</label>
                        <input type="number" id="filterPersen" class="form-control me-2" value="70" min="1" max="200"
                            style="width:150px;">
                        <button class="btn btn-primary btn-sm" onclick="updateChart()">Terapkan</button>
                    </div>
                @endif

                <!-- Filter Tahun (Admin, Manajer, User) -->
                @if(in_array(auth()->user()->role, ['admin', 'manajer', 'user']))
                    <div class="mb-3 d-flex align-items-center">
                        <label for="filterTahun" class="me-2 fw-bold" style="width: 180px;">Filter Tahun :</label>
                        <select id="filterTahun" class="form-select me-2" style="width:150px;">
                            @foreach ($years as $year)
                                <option value="{{ $year }}" {{ request('tahun', $selectedYear) == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                        <button id="btnTerapkanTahun" class="btn btn-primary btn-sm">Terapkan</button>
                    </div>
                @endif

                <script>
                    function applyFilters(extraParams = {}) {
                        const url = new URL(window.location.href);

                        // Parameter yang harus dipertahankan
                        const keepParams = ['kategori', 'persen', 'tahun'];
                        keepParams.forEach(param => {
                            const value = url.searchParams.get(param);
                            if (value !== null && !extraParams[param]) {
                                url.searchParams.set(param, value);
                            }
                        });

                        // Tambahkan / ubah parameter baru
                        Object.keys(extraParams).forEach(key => {
                            url.searchParams.set(key, extraParams[key]);
                        });

                        window.location.href = url.toString();
                    }

                    // Filter Persen
                    const btnPersen = document.getElementById('btnTerapkanPersen');
                    if (btnPersen) {
                        btnPersen.addEventListener('click', function () {
                            const persen = document.getElementById('filterPersen').value;
                            applyFilters({ persen: persen });
                        });
                    }

                    // Filter Tahun
                    const btnTahun = document.getElementById('btnTerapkanTahun');
                    if (btnTahun) {
                        btnTahun.addEventListener('click', function () {
                            const tahun = document.getElementById('filterTahun').value;
                            applyFilters({ tahun: tahun });
                        });
                    }
                </script>

                <div class="row g-4 mt-3">
                    <!-- Diagram 70% -->
                    <div class="col-md-6">
                        <div class="card card-chart">
                            <h6 id="chartTitle">Diagram Anggaran (70%)</h6>
                            <canvas id="barChart70"></canvas>
                        </div>
                    </div>

                    <!-- Diagram 100% -->
                    <div class="col-md-6">
                        <div class="card card-chart">
                            <h6>Diagram Anggaran (100%)</h6>
                            <canvas id="barChart100"></canvas>
                        </div>
                    </div>
                </div>

                <style>
                    /* Pastikan card dan chart bisa menyesuaikan ukuran layar */
                    .card-chart {
                        background: #fff;
                        border-radius: 12px;
                        border: 1px solid #e0e0e0;
                        transition: transform 0.2s ease, box-shadow 0.2s ease;
                    }

                    .card-chart:hover {
                        transform: translateY(-3px);
                        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                    }

                    /* Kontainer agar canvas proporsional */
                    .chart-container {
                        position: relative;
                        width: 100%;
                        height: 350px;
                        /* default tinggi di desktop */
                    }

                    /* Responsif: di layar kecil, tinggi grafik menyesuaikan */
                    @media (max-width: 768px) {
                        .chart-container {
                            height: 250px;
                        }
                    }

                    @media (max-width: 576px) {
                        .chart-container {
                            height: 200px;
                        }

                        .card-chart h6 {
                            font-size: 14px;
                        }
                    }
                </style>

                <!-- 📊 Ringkasan Program Kerja -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card text-center shadow-sm border-0 summary-card">
                            <div class="card-body">
                                <i class="bi bi-folder2-open fs-3 mb-2 text-primary"></i>
                                <h6 class="text-muted">Total Program Kerja</h6>
                                <h3 class="fw-bold text-primary">{{ $totalProgram }}</h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card text-center shadow-sm border-0 summary-card">
                            <div class="card-body">
                                <i class="bi bi-hourglass-split fs-3 mb-2 text-warning"></i>
                                <h6 class="text-muted">Sedang Dikerjakan</h6> {{-- ✅ ubah label --}}
                                <h3 class="fw-bold text-warning">{{ $programProses }}</h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card text-center shadow-sm border-0 summary-card">
                            <div class="card-body">
                                <i class="bi bi-exclamation-circle fs-3 mb-2 text-danger"></i>
                                <h6 class="text-muted">Belum Selesai</h6>
                                <h3 class="fw-bold text-danger">{{ $programBelumSelesai }}</h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card text-center shadow-sm border-0 summary-card">
                            <div class="card-body">
                                <i class="bi bi-check-circle fs-3 mb-2 text-success"></i>
                                <h6 class="text-muted">Selesai</h6>
                                <h3 class="fw-bold text-success">{{ $programSelesai }}</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 🎨 CSS Langsung di bawah Blade -->
                <style>
                    .summary-card {
                        transition: all 0.3s ease;
                        border-radius: 12px;
                        background: #fff;
                    }

                    .summary-card:hover {
                        transform: translateY(-5px);
                        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
                    }

                    .summary-card .card-body {
                        padding: 1.5rem;
                    }

                    .summary-card h6 {
                        font-weight: 600;
                        letter-spacing: 0.3px;
                    }

                    .summary-card i {
                        display: block;
                    }
                </style>

                <!-- Program Kerja Detail Section -->
                <h3 @class(['mt-5', 'mb-4'])>Program Kerja</h3>
                <div @class(['row', 'g-4', 'mb-4'])>

                    <!-- DETAIL ANGGARAN -->
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        @foreach ($kategori as $namaKategori)
                            @php
                                $anggaran = $anggarans[$namaKategori] ?? null;
                                $total = $anggaran->total ?? 0;
                                $terserap = $anggaran->terserap ?? 0;
                                $tersisa = $anggaran->tersisa ?? 0;
                                $totalDanaTambahan = $anggaran ? \App\Models\RiwayatDana::where('anggaran_id', $anggaran->id)->sum('jumlah') : 0;

                                if (!function_exists('formatRupiahSingkat')) {
                                    function formatRupiahSingkat($value)
                                    {
                                        if ($value >= 1000000000)
                                            return rtrim(rtrim(number_format($value / 1000000000, 1, ',', ''), '0'), ',') . 'M';
                                        elseif ($value >= 1000000)
                                            return rtrim(rtrim(number_format($value / 1000000, 1, ',', ''), '0'), ',') . 'JT';
                                        elseif ($value >= 1000)
                                            return rtrim(rtrim(number_format($value / 1000, 1, ',', ''), '0'), ',') . 'RB';
                                        return number_format($value, 0, ',', '.');
                                    }
                                }
                            @endphp

                            <div class="col">
                                <div class="card anggaran-card shadow-sm border-0 rounded-4 h-100"
                                    data-kategori="{{ Str::slug($namaKategori) }}"
                                    data-tahun="{{ $anggaran?->created_at?->format('Y') ?? '' }}">

                                    <div class="card-body text-center">
                                        <h6 class="fw-bold text-dark mb-3">{{ $namaKategori }}</h6>

                                        <div class="text-start mb-2">
                                            <small class="text-muted d-block">Anggaran Total:</small>
                                            <span class="fw-semibold">Rp {{ formatRupiahSingkat($total) }}</span>
                                        </div>
                                        <div class="text-start mb-2">
                                            <small class="text-muted d-block">Anggaran Terserap:</small>
                                            <span class="fw-semibold">Rp {{ formatRupiahSingkat($terserap) }}</span>
                                        </div>
                                        <div class="text-start mb-3">
                                            <small class="text-muted d-block">Anggaran Tersisa:</small>
                                            <span class="fw-semibold">Rp {{ formatRupiahSingkat($tersisa) }}</span>
                                        </div>

                                        <div class="border-top pt-2 text-start">
                                            @if ($anggaran)
                                            @else
                                            @endif
                                        </div>
                                    </div>

                                    <div class="card-footer bg-transparent border-0 d-flex flex-column gap-2 px-3 pb-3">

                                        {{-- 🔹 Tombol Detail tampil untuk semua --}}
                                        <button class="btn btn-light border rounded-pill fw-semibold" data-bs-toggle="modal"
                                            data-bs-target="#detailAnggaran{{ Str::slug($namaKategori) }}">
                                            Detail
                                        </button>

                                        @php
                                            $role = auth()->user()->role ?? null;
                                            // cek anggaran awal berdasarkan kolom jumlah
                                            $anggaranAwalSudahAda = $anggaran && $anggaran->jumlah > 0;
                                        @endphp

                                        {{-- 🔹 Tombol Kelola Dana muncul untuk admin & manajer jika anggaran sudah ada --}}
                                        @if ($anggaran && ($role == 'admin' || $role == 'manajer'))
                                            <button class="btn btn-secondary rounded-pill fw-semibold" data-bs-toggle="modal"
                                                data-bs-target="#kelolaDana{{ Str::slug($namaKategori) }}">
                                                Kelola Dana
                                            </button>
                                        @endif

                                        {{-- 🔹 Tambah Anggaran
                                        MUNCUL jika:
                                        - admin
                                        - BELUM ADA data anggaran di tahun & kategori ini
                                        --}}
                                        @if ($role === 'admin' && !$anggaran)
                                            <button class="btn btn-success rounded-pill fw-semibold" data-bs-toggle="modal"
                                                data-bs-target="#tambahAnggaran{{ Str::slug($namaKategori) }}">
                                                Tambah Anggaran
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <style>
                                    .anggaran-card {
                                        background-color: #f8f9fa;
                                        border-radius: 20px !important;
                                        transition: all 0.3s ease;
                                    }

                                    .anggaran-card:hover {
                                        transform: translateY(-5px);
                                        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
                                    }

                                    .anggaran-card .card-body {
                                        padding: 20px 25px;
                                    }

                                    .anggaran-card .btn {
                                        width: 100%;
                                        padding: 8px 0;
                                        font-size: 14px;
                                    }

                                    .anggaran-card .btn-light {
                                        background-color: #e9ecef;
                                        color: #333;
                                        border: none;
                                    }

                                    .anggaran-card .btn-light:hover {
                                        background-color: #dfe3e6;
                                    }

                                    .anggaran-card .btn-secondary {
                                        background-color: #545454;
                                        border: none;
                                    }

                                    .anggaran-card .btn-secondary:hover {
                                        background-color: #999fa5;
                                    }

                                    .anggaran-card .btn-success {
                                        background-color: #198754;
                                    }

                                    .anggaran-card .btn-success:hover {
                                        background-color: #157347;
                                    }
                                </style>

                                @if($anggaran)
                                    <!-- MODAL KELOLA DANA -->
                                    <div class="modal fade" id="kelolaDana{{ Str::slug($namaKategori) }}" tabindex="-1"
                                        aria-labelledby="kelolaDanaLabel{{ Str::slug($namaKategori) }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-xl">
                                            <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">

                                                <!-- HEADER -->
                                                <div class="modal-header bg-dark text-white">
                                                    <h5 class="modal-title fw-bold">
                                                        Kelola Dana - {{ $namaKategori }}
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white"
                                                        data-bs-dismiss="modal"></button>
                                                </div>

                                                <!-- BODY -->
                                                <div class="modal-body">
                                                    <!-- Tabs -->
                                                    <ul class="nav nav-tabs" id="tabDana{{ Str::slug($namaKategori) }}"
                                                        role="tablist">
                                                        <li class="nav-item" role="presentation">
                                                            <button class="nav-link active fw-bold"
                                                                id="tambah-tab{{ Str::slug($namaKategori) }}"
                                                                data-bs-toggle="tab"
                                                                data-bs-target="#tambah{{ Str::slug($namaKategori) }}"
                                                                type="button" role="tab">
                                                                Tambah Dana
                                                            </button>
                                                        </li>
                                                        <li class="nav-item" role="presentation">
                                                            <button class="nav-link fw-bold"
                                                                id="riwayat-tab{{ Str::slug($namaKategori) }}"
                                                                data-bs-toggle="tab"
                                                                data-bs-target="#riwayat{{ Str::slug($namaKategori) }}"
                                                                type="button" role="tab">
                                                                Riwayat Dana
                                                            </button>
                                                        </li>
                                                        <li class="nav-item" role="presentation">
                                                            <button class="nav-link fw-bold"
                                                                id="transfer-tab{{ Str::slug($namaKategori) }}"
                                                                data-bs-toggle="tab"
                                                                data-bs-target="#transfer{{ Str::slug($namaKategori) }}"
                                                                type="button" role="tab">
                                                                Transfer Dana
                                                            </button>
                                                        </li>
                                                    </ul>

                                                    <!-- Tabs Content -->
                                                    <div class="tab-content mt-4">

                                                        <!-- Tambah Dana -->
                                                        <div class="tab-pane fade show active"
                                                            id="tambah{{ Str::slug($namaKategori) }}" role="tabpanel">
                                                            <form action="{{ route('anggaran.tambahDana', $anggaran->id) }}"
                                                                method="POST">
                                                                @csrf
                                                                <div class="row g-3">
                                                                    <!-- Ganti input jadi seperti ini -->
                                                                    <div class="col-md-6">
                                                                        <label class="form-label fw-semibold">Jumlah Tambahan
                                                                            Dana (Rp)</label>
                                                                        <input type="text" name="jumlah"
                                                                            class="form-control format-rupiah" required
                                                                            placeholder="Masukkan jumlah dana">
                                                                    </div>

                                                                    <!-- Script format rupiah otomatis -->
                                                                    <script>
                                                                        document.addEventListener('DOMContentLoaded', function () {
                                                                            const rupiahInputs = document.querySelectorAll('.format-rupiah');

                                                                            rupiahInputs.forEach(function (input) {
                                                                                input.addEventListener('input', function (e) {
                                                                                    let value = e.target.value.replace(/\D/g, ''); // hapus non-digit
                                                                                    if (value) {
                                                                                        e.target.value = new Intl.NumberFormat('id-ID').format(value);
                                                                                    } else {
                                                                                        e.target.value = '';
                                                                                    }
                                                                                });

                                                                                // Pastikan saat form dikirim, titik dihapus
                                                                                const form = input.closest('form');
                                                                                if (form) {
                                                                                    form.addEventListener('submit', function () {
                                                                                        input.value = input.value.replace(/\./g, '');
                                                                                    });
                                                                                }
                                                                            });
                                                                        });
                                                                    </script>

                                                                    <div class="col-md-6">
                                                                        <label class="form-label fw-semibold">Tanggal Penambahan
                                                                            Dana</label>
                                                                        <input type="date" name="tanggal" class="form-control"
                                                                            value="{{ date('Y-m-d') }}" required>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label fw-semibold">Sumber
                                                                            Dana</label>
                                                                        <input type="text" name="sumber" class="form-control"
                                                                            required>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label fw-semibold">Keterangan
                                                                            Singkat</label>
                                                                        <input type="text" name="keterangan"
                                                                            class="form-control"
                                                                            placeholder="Contoh: Tambahan anggaran proyek A"
                                                                            required>
                                                                    </div>
                                                                    <div class="col-12">
                                                                        <label class="form-label fw-semibold">Deskripsi
                                                                            Lengkap</label>
                                                                        <textarea name="deskripsi" class="form-control" rows="3"
                                                                            required></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="text-end mt-3">
                                                                    <button type="submit" class="btn btn-success fw-bold">
                                                                        Simpan Tambahan Dana
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>

                                                        <!-- Riwayat Dana -->
                                                        <div class="tab-pane fade" id="riwayat{{ Str::slug($namaKategori) }}"
                                                            role="tabpanel">
                                                            @php
                                                                // Ambil data riwayat dana untuk kategori ini
                                                                $riwayat = \App\Models\RiwayatDana::where('anggaran_id', $anggaran->id)
                                                                    ->orderBy('tanggal', 'desc') // urutkan berdasarkan tanggal terbaru
                                                                    ->orderBy('created_at', 'desc') // jika tanggal sama, yang baru dibuat di atas
                                                                    ->get();
                                                            @endphp

                                                            @if($riwayat->isEmpty())
                                                                <div class="alert alert-warning text-center">
                                                                    Belum ada riwayat penambahan dana.
                                                                </div>
                                                            @else
                                                                <div class="table-responsive">
                                                                    <table class="table table-striped align-middle">
                                                                        <thead class="table-success">
                                                                            <tr>
                                                                                <th>No</th>
                                                                                <th>Tanggal</th>
                                                                                <th>Jumlah (Rp)</th>
                                                                                <th>Sumber Dana</th>
                                                                                <th>Keterangan</th>
                                                                                <th>Deskripsi</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach ($riwayat as $index => $item)
                                                                                <tr>
                                                                                    {{-- Nomor urut dimulai dari 1 untuk data terbaru
                                                                                    --}}
                                                                                    <td>{{ $index + 1 }}</td>

                                                                                    {{-- Format tanggal Indonesia (dd/mm/yyyy) --}}
                                                                                    <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}
                                                                                    </td>

                                                                                    {{-- Format jumlah dengan pemisah ribuan --}}
                                                                                    <td>Rp
                                                                                        {{ number_format($item->jumlah, 0, ',', '.') }}
                                                                                    </td>

                                                                                    <td>{{ $item->sumber }}</td>
                                                                                    <td>{{ $item->keterangan }}</td>
                                                                                    <td>{{ $item->deskripsi }}</td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <!-- Transfer Dana -->
                                                        <div class="tab-pane fade" id="transfer{{ Str::slug($namaKategori) }}"
                                                            role="tabpanel">
                                                            <form action="{{ route('anggaran.transferDana', $anggaran->id) }}"
                                                                method="POST">
                                                                @csrf
                                                                <div class="row g-3">
                                                                    <div class="col-md-6">
                                                                        <label class="form-label fw-semibold">Pilih Tujuan
                                                                            Transfer Dana</label>
                                                                        <select name="tujuan" class="form-select" required>
                                                                            <option value="">-- Pilih Anggaran Tujuan --
                                                                            </option>
                                                                            @foreach ($semuaAnggaran as $ang)
                                                                                @if ($ang->id !== $anggaran->id)
                                                                                    <option value="{{ $ang->id }}">{{ $ang->nama }}
                                                                                    </option>
                                                                                @endif
                                                                            @endforeach
                                                                        </select>
                                                                    </div>

                                                                    <!-- Jumlah Dana yang Ditransfer -->
                                                                    <div class="col-md-6">
                                                                        <label class="form-label fw-semibold">Jumlah Dana yang
                                                                            Ditransfer (Rp)</label>
                                                                        <input type="text" name="jumlah_transfer"
                                                                            class="form-control format-rupiah"
                                                                            placeholder="Masukkan jumlah dana" required>
                                                                    </div>

                                                                    <!-- Script format otomatis -->
                                                                    <script>
                                                                        document.addEventListener('DOMContentLoaded', function () {
                                                                            // Ambil semua input dengan class .format-rupiah
                                                                            const rupiahInputs = document.querySelectorAll('.format-rupiah');

                                                                            rupiahInputs.forEach(input => {
                                                                                // Format angka setiap kali diketik
                                                                                input.addEventListener('input', function (e) {
                                                                                    let value = e.target.value.replace(/\D/g, ''); // hapus semua non-digit
                                                                                    if (value) {
                                                                                        e.target.value = new Intl.NumberFormat('id-ID').format(value);
                                                                                    } else {
                                                                                        e.target.value = '';
                                                                                    }
                                                                                });

                                                                                // Saat form dikirim, hapus semua titik supaya disimpan sebagai angka
                                                                                const form = input.closest('form');
                                                                                if (form) {
                                                                                    form.addEventListener('submit', function () {
                                                                                        input.value = input.value.replace(/\./g, '');
                                                                                    });
                                                                                }
                                                                            });
                                                                        });
                                                                    </script>

                                                                    <div class="col-md-6">
                                                                        <label class="form-label fw-semibold">Tanggal
                                                                            Transfer</label>
                                                                        <input type="date" name="tanggal" class="form-control"
                                                                            value="{{ date('Y-m-d') }}" required>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label fw-semibold">Keterangan</label>
                                                                        <input type="text" name="keterangan"
                                                                            class="form-control"
                                                                            placeholder="Contoh: Tambahan dari revisi anggaran">
                                                                    </div>
                                                                </div>
                                                                <div class="text-end mt-3">
                                                                    <button type="submit"
                                                                        class="btn btn-success fw-bold text-white">
                                                                        Transfer Dana
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Modal Tambah Anggaran -->
                            <div @class(['modal', 'fade']) id="tambahAnggaran{{ Str::slug($namaKategori) }}" tabindex="-1"
                                aria-hidden="true">
                                <div @class(['modal-dialog'])>
                                    <div @class(['modal-content'])>
                                        <form action="{{ route('anggaran.store') }}" method="POST">
                                            @csrf
                                            <div @class(['modal-header'])>
                                                <h5 @class(['modal-title'])>Tambah Anggaran - {{ $namaKategori }}</h5>
                                                <button type="button" @class(['btn-close'])
                                                    data-bs-dismiss="modal"></button>
                                            </div>

                                            <div @class(['modal-body'])>
                                                <input type="hidden" name="nama" value="{{ $namaKategori }}">

                                                <div @class(['mb-3'])>
                                                    <label @class(['form-label'])>Total Anggaran</label>
                                                    <input type="text" id="totalAnggaran{{ Str::slug($namaKategori) }}"
                                                        name="total" @class(['form-control']) required
                                                        placeholder="Masukkan nominal">
                                                </div>

                                                <!-- Tambahan input Tahun -->
                                                <div class="mb-3">
                                                    <label class="form-label">Tahun</label>
                                                    <input type="number" name="tahun" class="form-control" required
                                                        value="{{ date('Y') }}">
                                                </div>
                                                <!-- Akhir tambahan Tahun -->
                                            </div>

                                            <div @class(['modal-footer'])>
                                                <button type="button" @class(['btn', 'btn-secondary'])
                                                    data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" @class(['btn', 'btn-success'])>Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Script Format Otomatis -->
                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    const input = document.getElementById('totalAnggaran{{ Str::slug($namaKategori) }}');

                                    input.addEventListener('input', function (e) {
                                        // Hapus semua karakter selain angka
                                        let value = e.target.value.replace(/\D/g, '');

                                        // Format dengan titik setiap 3 digit
                                        value = new Intl.NumberFormat('id-ID').format(value);

                                        // Tampilkan kembali di input
                                        e.target.value = value;
                                    });

                                    // Saat form dikirim, ubah ke angka tanpa titik
                                    input.form.addEventListener('submit', function () {
                                        input.value = input.value.replace(/\./g, '');
                                    });
                                });
                            </script>

                            <!-- Modal Detail -->
                            <div @class(['modal', 'fade']) id="detailAnggaran{{ Str::slug($namaKategori) }}" tabindex="-1"
                                aria-hidden="true">
                                <div @class(['modal-dialog', 'modal-lg'])>
                                    <div @class(['modal-content'])>

                                        <!-- Header -->
                                        <div @class(['modal-header'])>
                                            <h5 @class(['modal-title'])>Detail Anggaran – {{ $namaKategori }}</h5>
                                            <button type="button" @class(['btn-close']) data-bs-dismiss="modal"></button>
                                        </div>

                                        <!-- Body -->
                                        <div @class(['modal-body'])>

                                            @if($anggaran && $anggaran->programKerjas->count())
                                                <ul class="list-group">
                                                    @foreach ($anggaran->programKerjas as $program)
                                                        <li class="list-group-item">
                                                            <div class="fw-bold">
                                                                {{ $program->nama_pekerjaan }}
                                                            </div>
                                                            <div class="mt-1">
                                                                <span class="text-muted">Nama Pekerjaan :</span>
                                                                {{ $program->deskripsi }}
                                                            </div>
                                                            <div>
                                                                <span class="text-muted">Biaya :</span>
                                                                <strong>Rp {{ number_format($program->dana, 0, ',', '.') }}</strong>
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p>Belum ada penggunaan anggaran.</p>
                                            @endif

                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Program Kerja -->
                    <div class="card mt-4 p-4">
                        <h5 class="mb-3">Program Kerja</h5>

                        <!-- Filter Anggaran -->
                        <div class="mb-4 d-flex flex-wrap align-items-center gap-3">
                            <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center gap-2">
                                <label for="filterKategori" class="fw-semibold me-2">Filter Kategori:</label>
                                <select id="filterKategori" name="filterKategori" class="form-select"
                                    style="width: 220px;">
                                    <option value="all" {{ request('filterKategori') == 'all' ? 'selected' : '' }}>
                                        Keseluruhan Anggaran
                                    </option>
                                    @foreach ($kategori as $namaKategori)
                                        <option value="{{ $namaKategori }}" {{ request('filterKategori') == $namaKategori ? 'selected' : '' }}>
                                            {{ $namaKategori }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const filterKategori = document.getElementById('filterKategori');
                                const form = filterKategori.closest('form');

                                // otomatis submit saat kategori diubah
                                filterKategori.addEventListener('change', function () {
                                    form.submit();
                                });
                            });
                        </script>

                        <!-- Input Pencarian (Bisa diakses Admin, Manajer, dan User) -->
                        <form method="GET" action="{{ url()->current() }}" class="mb-3" id="searchForm">
                            <div class="input-group">
                                <input type="text" id="searchInput" name="search" class="form-control"
                                    placeholder="Cari program kerja" value="{{ request('search') }}">
                            </div>
                        </form>

                        <script>
                            // 🔹 Fungsi format angka ke format ribuan (dengan titik)
                            function formatRibuan(angka) {
                                if (!angka) return "";
                                return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            }

                            const searchInput = document.getElementById("searchInput");
                            const searchForm = document.getElementById("searchForm");

                            if (searchInput) {
                                searchInput.addEventListener("input", function () {
                                    let inputVal = this.value;

                                    // Cek apakah input mirip tanggal (mengandung - atau /)
                                    let isTanggal = /[-/]/.test(inputVal);

                                    // Jika hanya angka → auto format ribuan
                                    if (!isTanggal && /^[0-9.]+$/.test(inputVal)) {
                                        let numericOnly = inputVal.replace(/\D/g, "");
                                        this.value = formatRibuan(numericOnly);
                                    }

                                    // 🔹 Debounce submit (cegah spam request)
                                    clearTimeout(this.timer);
                                    this.timer = setTimeout(() => {
                                        searchForm.submit();
                                    }, 500);
                                });
                            }
                        </script>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>NAMA PEKERJAAN</th>
                                        <th>PENANGGUNG JAWAB</th>
                                        <th>VENDOR</th>
                                        <th>WAKTU DIMULAI</th>
                                        <th>TARGET WAKTU</th>
                                        <th>STATUS</th>
                                        <th>KATEGORI</th>
                                        <th>BIAYA</th>
                                        <th class="text-center pe-4">DETAIL</th>
                                        <th class="text-end pe-4">AKSI</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach($programKerjas as $program)
                                        <tr data-kategori="{{ $program->kategori ?? 'Umum' }}">
                                            <td>{{ $program->deskripsi }}</td>
                                            <td>{{ $program->penanggung_jawab }}</td>
                                            <td>{{ $program->nama_vendor ?? '-' }}</td>
                                            <td>{{ $program->created_at->format('d-m-Y') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($program->target_waktu)->format('d-m-Y') }}</td>

                                            {{-- STATUS BADGE --}}
                                            <td>
                                                @php
                                                    // Ubah otomatis teks "Proses" atau "Dalam Proses" menjadi "Tervalidasi" (untuk TAMPILAN SAJA)
                                                    if ($program->status == 'Proses' || $program->status == 'Dalam Proses') {
                                                        $status = 'Tervalidasi';
                                                    } else {
                                                        $status = $program->status;
                                                    }

                                                    // Tetap dibiarkan agar tidak mengganggu fungsi lain
                                                    $statusFinal = $status;
                                                @endphp

                                                <span class="badge
                                                                                @if($program->status == 'Belum Selesai') bg-danger text-white
                                                                                @elseif($program->status == 'Sedang Dikerjakan' || $program->status == 'Proses' || $program->status == 'Dalam Proses') bg-warning text-dark
                                                                                @elseif(in_array($program->status, ['Menunggu Validasi Kasi', 'Menunggu Persetujuan', 'Menunggu Validasi Manajer'])) bg-secondary
                                                                                @elseif($program->status == 'Selesai') bg-success
                                                                                @else bg-secondary
                                                                                @endif">
                                                    {{-- Tampilan khusus untuk nama status tertentu --}}
                                                    @if($status == 'Menunggu Persetujuan Selesai')
                                                        Menunggu Approval
                                                    @else
                                                        {{ $status }}
                                                    @endif
                                                </span>
                                            </td>


                                            <td>{{ $program->kategori ?? '-' }}</td>
                                            <td>
                                                @php
                                                    // Dana utama dari tabel program_kerjas
                                                    $danaUtama = $program->dana ?? 0;

                                                    // Dana tambahan dari relasi riwayat_dana_id (jika ada)
                                                    $danaTambahan = 0;
                                                    if ($program->riwayat_dana_id) {
                                                        $riwayat = \App\Models\RiwayatDana::find($program->riwayat_dana_id);
                                                        $danaTambahan = $program->dana_tambahan_dipakai ?? 0;
                                                    }

                                                    // Total dipakai = dana utama + dana tambahan (jika ada)
                                                    $totalDipakai = $danaUtama + $danaTambahan;
                                                @endphp

                                                {{ $totalDipakai > 0 ? 'Rp ' . number_format($totalDipakai, 0, ',', '.') : '-' }}
                                            </td>

                                            {{-- Tombol Detail --}}
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                    data-bs-target="#detailProgram{{ $program->id }}">
                                                    Detail
                                                </button>
                                            </td>

                                            {{-- Kolom Aksi --}}
                                            <td class="align-middle text-end pe-4" style="width: 160px;">
                                                @php
                                                    $latest = \App\Models\ProgressKerja::where('program_kerja_id', $program->id)->latest()->first();
                                                    $role = auth()->user()->role;
                                                @endphp

                                                <div class="d-flex flex-column align-items-end justify-content-between"
                                                    style="height: 100%;">

                                                    {{-- 🔹 Dropdown Menu Aksi --}}
                                                    <div class="dropdown d-flex justify-content-center align-items-center">
                                                        <button
                                                            class="btn btn-light rounded-circle d-flex justify-content-center align-items-center shadow-sm"
                                                            type="button" id="aksiMenu{{ $program->id }}"
                                                            data-bs-toggle="dropdown" aria-expanded="false"
                                                            style="width: 36px; height: 36px; font-size: 1.2rem; padding: 0;">
                                                            <i class="bi bi-three-dots-vertical"></i>
                                                        </button>

                                                        <ul class="dropdown-menu dropdown-menu-end text-center shadow"
                                                            aria-labelledby="aksiMenu{{ $program->id }}">

                                                            {{-- ============================ --}}
                                                            {{-- ADMIN ROLE : Update & Hapus --}}
                                                            {{-- ============================ --}}
                                                            @if($role === 'admin')

                                                                {{-- ❗ Tambahkan syarat: sembunyikan tombol UPDATE jika status =
                                                                Belum Selesai --}}
                                                                @if(
                                                                        $program->status !== 'Selesai' &&
                                                                        $program->status !== 'Menunggu Validasi Manajer' &&
                                                                        trim($program->status) !== 'Belum Selesai'
                                                                    )
                                                                    <li>
                                                                        <button
                                                                            class="dropdown-item d-flex align-items-center justify-content-center"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#progressModal{{ $program->id }}">
                                                                            <i class="bi bi-pencil-square me-2"></i> Update
                                                                        </button>
                                                                    </li>
                                                                @endif

                                                                {{-- Tombol hapus tetap muncul --}}
                                                                <li>
                                                                    <form
                                                                        action="{{ route('program-kerja.destroy', $program->id) }}"
                                                                        method="POST" class="delete-form m-0">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="button"
                                                                            class="dropdown-item text-danger d-flex align-items-center justify-content-center btn-delete">
                                                                            <i class="bi bi-trash me-2"></i> Hapus
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            @endif

                                                            {{-- ================================== --}}
                                                            {{-- MANAJER ROLE : Validasi / Approve --}}
                                                            {{-- ================================== --}}
                                                            @if($role === 'manajer')
                                                                @if($program->status === 'Menunggu Validasi Manajer')
                                                                    <li>
                                                                        <form
                                                                            action="{{ route('program-kerja.validate', $program->id) }}"
                                                                            method="POST" class="m-0">
                                                                            @csrf
                                                                            <button type="submit"
                                                                                class="dropdown-item d-flex align-items-center justify-content-center text-primary">
                                                                                <i class="bi bi-check2-circle me-2"></i> Validasi
                                                                                Program
                                                                            </button>
                                                                        </form>
                                                                    </li>
                                                                @endif

                                                                @if($latest && $latest->bukti_dokumen && $latest->status_verifikasi === 'pending')
                                                                    <li>
                                                                        <form action="{{ route('progres.approve', $latest->id) }}"
                                                                            method="POST" class="m-0">
                                                                            @csrf
                                                                            <button
                                                                                class="dropdown-item text-success d-flex align-items-center justify-content-center">
                                                                                <i class="bi bi-check-lg me-2"></i> Approve Bukti
                                                                            </button>
                                                                        </form>
                                                                    </li>
                                                                @endif

                                                                {{-- Hapus juga untuk manajer jika diperbolehkan --}}
                                                                <li>
                                                                    <form
                                                                        action="{{ route('programkerja.destroy', $program->id) }}"
                                                                        method="POST" class="delete-form m-0">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="button"
                                                                            class="dropdown-item text-danger d-flex align-items-center justify-content-center btn-delete">
                                                                            <i class="bi bi-trash me-2"></i> Hapus
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>

                                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                            <script>
                                                document.addEventListener('DOMContentLoaded', function () {
                                                    // Pasang event listener untuk semua tombol hapus
                                                    document.querySelectorAll('.btn-delete').forEach(button => {
                                                        button.addEventListener('click', function (e) {
                                                            e.preventDefault();
                                                            const form = this.closest('form');

                                                            Swal.fire({
                                                                title: 'Yakin ingin menghapus?',
                                                                text: "Data program kerja ini akan dihapus secara permanen.",
                                                                icon: 'warning',
                                                                showCancelButton: true,
                                                                confirmButtonColor: '#d33',
                                                                cancelButtonColor: '#6c757d',
                                                                confirmButtonText: 'Ya, hapus',
                                                                cancelButtonText: 'Batal'
                                                            }).then((result) => {
                                                                if (result.isConfirmed) {
                                                                    form.submit();
                                                                }
                                                            });
                                                        });
                                                    });
                                                });
                                            </script>

                                            <!-- MODAL UPDATE PROGRES -->
                                            <div class="modal fade" id="progressModal{{ $program->id }}" tabindex="-1"
                                                aria-labelledby="progressModalLabel{{ $program->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"
                                                                id="progressModalLabel{{ $program->id }}">
                                                                Update Progres – {{ $program->deskripsi }}
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>

                                                        @php
                                                            $progress = \App\Models\ProgressKerja::where('program_kerja_id', $program->id)->latest()->first();
                                                        @endphp

                                                        <!-- 🔹 Gunakan form dinamis: update jika sudah ada, store jika belum -->
                                                        <form action="{{ route('progres.update', $program->id) }}"
                                                            method="POST" enctype="multipart/form-data">
                                                            @csrf
                                                            @method('PUT')

                                                            <div class="modal-body">
                                                                <!-- Input tersembunyi agar tahu program kerja mana -->
                                                                <input type="hidden" name="program_kerja_id"
                                                                    value="{{ $program->id }}">

                                                                <!-- Input tanggal -->
                                                                <div class="mb-3">
                                                                    <label for="tanggal-{{ $program->id }}"
                                                                        class="form-label">Tanggal</label>
                                                                    <input type="date" class="form-control"
                                                                        id="tanggal-{{ $program->id }}" name="tanggal"
                                                                        value="{{ old('tanggal', $progress->tanggal ?? \Carbon\Carbon::now()->format('Y-m-d')) }}"
                                                                        min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                                                                        required>
                                                                </div>

                                                                <!-- Input catatan -->
                                                                <div class="mb-3">
                                                                    <label for="catatan-{{ $program->id }}"
                                                                        class="form-label">Catatan</label>
                                                                    <textarea class="form-control"
                                                                        id="catatan-{{ $program->id }}" name="catatan"
                                                                        required>{{ old('catatan', $progress->catatan ?? '') }}</textarea>
                                                                </div>

                                                                <!-- Pilihan status -->
                                                                <div class="mb-3">
                                                                    <label for="status-{{ $program->id }}"
                                                                        class="form-label">Status</label>
                                                                    <select name="status" class="form-select"
                                                                        id="status-{{ $program->id }}">
                                                                        <option value="Belum Selesai" {{ (old('status', $progress->status ?? '') == 'Belum Selesai') ? 'selected' : '' }}>Belum Selesai</option>
                                                                        <option value="Selesai" {{ (old('status', $progress->status ?? '') == 'Selesai') ? 'selected' : '' }}>Selesai</option>
                                                                    </select>
                                                                </div>

                                                                <!-- Upload Bukti Dokumen -->
                                                                <div class="mb-3 bukti-wrapper"
                                                                    id="bukti-wrapper-{{ $program->id }}"
                                                                    style="{{ (old('status', $progress->status ?? '') == 'Selesai') ? '' : 'display:none;' }}">

                                                                    <label for="bukti_dokumen-{{ $program->id }}"
                                                                        class="form-label">Bukti Dokumen</label>

                                                                    <input type="file" name="bukti_dokumen"
                                                                        id="bukti_dokumen-{{ $program->id }}"
                                                                        class="form-control" accept="application/pdf"
                                                                        onchange="validatePDF(this, {{ $program->id }})">

                                                                    <small class="text-muted">Format: PDF (max 5 MB)</small>

                                                                    <!-- Pesan sukses upload -->
                                                                    <div class="text-success mt-2 d-none"
                                                                        id="success-upload-{{ $program->id }}">
                                                                        ✅ Dokumen berhasil diupload
                                                                    </div>

                                                                    @if(!empty($progress->bukti_dokumen))
                                                                        <div class="mt-2">
                                                                            <a href="{{ asset('storage/' . $progress->bukti_dokumen) }}"
                                                                                target="_blank">
                                                                                Lihat Dokumen Sebelumnya
                                                                            </a>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit" class="btn btn-primary">Simpan
                                                                    Perubahan</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- SCRIPT untuk tampilkan bukti otomatis -->
                                            <script>
                                                document.addEventListener("DOMContentLoaded", function () {
                                                    const statusSelect = document.getElementById("status-{{ $program->id }}");
                                                    const buktiWrapper = document.getElementById("bukti-wrapper-{{ $program->id }}");
                                                    statusSelect.addEventListener("change", function () {
                                                        buktiWrapper.style.display = (this.value === "Selesai") ? "block" : "none";
                                                    });
                                                });

                                                // Validasi upload PDF & tampilkan centang sukses
                                                function validatePDF(input, id) {
                                                    const file = input.files[0];
                                                    const successMsg = document.getElementById(`success-upload-${id}`);

                                                    // Reset tampilan pesan
                                                    successMsg.classList.add("d-none");

                                                    if (file) {
                                                        if (file.type !== "application/pdf") {
                                                            alert("File harus dalam format PDF!");
                                                            input.value = "";
                                                            return;
                                                        }

                                                        if (file.size > 5 * 1024 * 1024) {
                                                            alert("Ukuran file maksimal 5 MB!");
                                                            input.value = "";
                                                            return;
                                                        }

                                                        // Jika lolos validasi -> tampilkan pesan sukses
                                                        successMsg.classList.remove("d-none");
                                                    }
                                                }
                                            </script>

                                            {{-- === Modal Detail Tiap Program === --}}
                                            <div class="modal fade" id="detailProgram{{ $program->id }}" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <style>
                                                            /* 🌟 Hilangkan fokus biru dari SEMUA elemen di dalam modal */
                                                            .modal-body *:focus,
                                                            .modal-body *:active,
                                                            .modal-body div:focus {
                                                                outline: none !important;
                                                                box-shadow: none !important;
                                                                border: none !important;
                                                            }

                                                            /* Hilangkan garis / border biru pada text yang readonly atau container */
                                                            .modal-body [contenteditable],
                                                            .modal-body .text-muted,
                                                            .modal-body div {
                                                                border: none !important;
                                                                outline: none !important;
                                                                box-shadow: none !important;
                                                                background: transparent !important;
                                                            }

                                                            /* Hover lembut di header */
                                                            .modal-header {
                                                                transition: background-color 0.3s ease;
                                                            }

                                                            .modal-header:hover {
                                                                background-color: #0b5ed7;
                                                                /* sedikit lebih gelap dari primary */
                                                            }

                                                            /* Animasi tombol Tutup */
                                                            .btn-animated {
                                                                transition: all 0.3s ease;
                                                            }

                                                            .btn-animated:hover {
                                                                transform: translateY(-2px);
                                                                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
                                                            }
                                                        </style>

                                                        <!-- 🧾 Detail Progres Kerja -->
                                                        <div class="modal-header bg-primary text-white rounded-top-4">
                                                            <h5 class="modal-title fw-bold mb-0">
                                                                <i class="bi bi-info-circle me-2"></i>
                                                                Detail Progres Kerja – {{ $program->nama_pekerjaan }}
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white"
                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>

                                                        <div class="modal-body px-4 py-4" style="background-color:#f9fafb;">
                                                            <!-- ✅ Detail Pekerjaan -->
                                                            <div class="border-start border-4 border-primary ps-3 mb-4">
                                                                <p class="fw-semibold mb-1 text-dark">Detail Pekerjaan:</p>
                                                                <div class="text-muted mb-0 user-select-none"
                                                                    style="border:none; outline:none; box-shadow:none; padding:0; background:none;">
                                                                    {{ $program->deskripsi_lengkap ?? 'Tidak ada deskripsi.' }}
                                                                </div>
                                                            </div>

                                                            <!-- 🔹 Informasi Utama -->
                                                            <div class="row g-3 mb-3">
                                                                <div class="col-md-6">
                                                                    <p class="fw-semibold mb-1 text-dark">Penanggung Jawab:
                                                                    </p>
                                                                    <p class="text-muted mb-0">
                                                                        {{ $program->penanggung_jawab }}
                                                                    </p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p class="fw-semibold mb-1 text-dark">Vendor:</p>
                                                                    <p class="text-muted mb-0">{{ $program->vendor ?? '-' }}
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            <!-- 🔹 Vendor & Kategori -->
                                                            <div class="row g-3 mb-3">
                                                                <div class="col-md-6">
                                                                    <p class="fw-semibold mb-1 text-dark">Nama Vendor:</p>
                                                                    <p class="text-muted mb-0">
                                                                        {{ $program->nama_vendor ?? '-' }}
                                                                    </p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p class="fw-semibold mb-1 text-dark">Kategori:</p>
                                                                    <p class="text-muted mb-0">
                                                                        {{ $program->kategori ?? '-' }}
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            <!-- 🔹 Target Waktu & Status -->
                                                            <div class="row g-3 align-items-center mb-4">
                                                                <div class="col-md-6">
                                                                    <p class="fw-semibold mb-1 text-dark">Target Waktu:</p>
                                                                    <p class="text-muted mb-0">
                                                                        {{ \Carbon\Carbon::parse($program->target_waktu)->format('d-m-Y') }}
                                                                    </p>
                                                                </div>
                                                                <div class="col-md-6 d-flex flex-column align-items-start">
                                                                    <p class="fw-semibold mb-1 text-dark">Status:</p>

                                                                    @php
                                                                        $status = $program->status == 'Dalam Proses' ? 'Sedang Dikerjakan' : $program->status;
                                                                    @endphp

                                                                    <span
                                                                        class="badge fs-6 px-3 py-2 rounded-pill shadow-sm
                                                                                                                                                                                    @if($status == 'Tervalidasi' || $status == 'Sedang Dikerjakan')
                                                                                                                                                                                        bg-warning text-dark
                                                                                                                                                                                    @elseif($status == 'Selesai')
                                                                                                                                                                                        bg-success
                                                                                                                                                                                    @elseif($status == 'Belum Selesai')
                                                                                                                                                                                        bg-danger
                                                                                                                                                                                    @else
                                                                                                                                                                                        bg-secondary
                                                                                                                                                                                     @endif">
                                                                        {{ $status }}
                                                                    </span>
                                                                </div>
                                                            </div>

                                                            <!-- 📝 Catatan Progres Otomatis -->
                                                            <div class="border-top pt-3">

                                                                <!-- 🔵 Judul -->
                                                                <div
                                                                    class="d-flex justify-content-between align-items-center mb-2">
                                                                    <p class="fw-semibold mb-0 text-dark">
                                                                        <i class="bi bi-journal-text me-1"></i> Catatan
                                                                        Progres:
                                                                    </p>
                                                                </div>

                                                                @if($program->progressKerjas && $program->progressKerjas->count() > 0)
                                                                    <ul class="list-group">
                                                                        @foreach($program->progressKerjas->sortByDesc('tanggal') as $progress)
                                                                            <li
                                                                                class="list-group-item d-flex justify-content-between align-items-start">
                                                                                <div>
                                                                                    <p class="mb-1">
                                                                                        <strong>{{ \Carbon\Carbon::parse($progress->tanggal)->format('d M Y') }}</strong>
                                                                                    </p>

                                                                                    <p class="mb-1 text-muted">
                                                                                        {{ $progress->catatan }}
                                                                                    </p>

                                                                                    @if(!empty($progress->persentase))
                                                                                        <p class="small mb-1 text-secondary">
                                                                                            <i class="bi bi-graph-up"></i> Progres:
                                                                                            {{ $progress->persentase }}%
                                                                                        </p>
                                                                                    @endif

                                                                                    @if($progress->bukti_dokumen)
                                                                                        <a href="{{ asset('storage/' . $progress->bukti_dokumen) }}"
                                                                                            target="_blank" class="small text-primary">
                                                                                            <i class="bi bi-file-earmark-text"></i>
                                                                                            Lihat Bukti Dokumen
                                                                                        </a>
                                                                                    @endif
                                                                                </div>

                                                                                <span
                                                                                    class="badge
                                                                                                                                                                @if($progress->status == 'Selesai') bg-success
                                                                                                                                                                @elseif($progress->status == 'Belum Selesai') bg-danger
                                                                                                                                                                @else bg-secondary
                                                                                                                                                                @endif
                                                                                                                                                                rounded-pill px-3 py-2">
                                                                                    {{ $progress->status }}
                                                                                </span>
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                @else
                                                                    <p class="text-muted fst-italic">Belum ada catatan progres
                                                                        yang tercatat.</p>
                                                                @endif
                                                            </div>
                                    @endforeach
                                                        {{-- ✅ Letakkan semua modal DI LUAR tabel --}}
                                                        @foreach($programKerjas as $program)
                                                            <div class="modal fade" id="progressModal{{ $program->id }}"
                                                                tabindex="-1"
                                                                aria-labelledby="progressModalLabel{{ $program->id }}"
                                                                aria-hidden="true">
                                                                <div class="modal-dialog">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title">Update Progres –
                                                                                {{ $program->deskripsi }}
                                                                            </h5>
                                                                            <button type="button" class="btn-close"
                                                                                data-bs-dismiss="modal"
                                                                                aria-label="Close"></button>
                                                                        </div>

                                                                        <form action="{{ route('progres.store') }}"
                                                                            method="POST" enctype="multipart/form-data">
                                                                            @csrf
                                                                            <input type="hidden" name="program_kerja_id"
                                                                                value="{{ $program->id }}">
                                                                            <div class="modal-body">
                                                                                <div class="mb-3">
                                                                                    <label
                                                                                        class="form-label">Tanggal</label>
                                                                                    <input type="date" class="form-control"
                                                                                        name="tanggal" required>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label
                                                                                        class="form-label">Catatan</label>
                                                                                    <textarea class="form-control"
                                                                                        name="catatan" required></textarea>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label class="form-label">Status</label>
                                                                                    <select name="status"
                                                                                        class="form-select"
                                                                                        id="status-{{ $program->id }}">
                                                                                        <option value="Belum Selesai"
                                                                                            selected>
                                                                                            Belum
                                                                                            Selesai
                                                                                        </option>
                                                                                        <option value="Selesai">Selesai
                                                                                        </option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="mb-3 bukti-wrapper"
                                                                                    id="bukti-wrapper-{{ $program->id }}"
                                                                                    style="display: none;">
                                                                                    <label class="form-label">Bukti
                                                                                        Dokumen</label>
                                                                                    <input type="file" name="bukti_dokumen"
                                                                                        class="form-control"
                                                                                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                                                                    <small class="text-muted">Format: PDF,
                                                                                        JPG,
                                                                                        PNG,
                                                                                        DOC (max 5
                                                                                        MB)</small>
                                                                                </div>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button"
                                                                                    class="btn btn-secondary"
                                                                                    data-bs-dismiss="modal">Batal</button>
                                                                                <button type="submit"
                                                                                    class="btn btn-primary">Simpan
                                                                                    Progres</button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <script>
                                                                document.addEventListener("DOMContentLoaded", function () {
                                                                    document.getElementById("searchProgram").addEventListener("keyup", function () {
                                                                        const keyword = this.value.toLowerCase().trim();
                                                                        const rows = document.querySelectorAll("table tbody tr");
                                                                        rows.forEach(row => {
                                                                            row.style.display = row.textContent.toLowerCase().includes(keyword) ? "" : "none";
                                                                        });
                                                                    });

                                                                    @foreach($programKerjas as $program)
                                                                        const statusSelect{{ $program->id }} = document.getElementById("status-{{ $program->id }}");
                                                                        const buktiWrapper{{ $program->id }} = document.getElementById("bukti-wrapper-{{ $program->id }}");
                                                                        statusSelect{{ $program->id }}?.addEventListener("change", function () {
                                                                            buktiWrapper{{ $program->id }}.style.display = (this.value === "Selesai") ? "block" : "none";
                                                                        });
                                                                    @endforeach
                                                                                                                                                                                                                                                                                                                                                                });
                                                            </script>

                                                            <!-- ========================= -->
                                                            <!-- 📜 MODAL DETAIL PROGRAM -->
                                                            <!-- ========================= -->
                                                            <div class="modal fade" id="detailProgram{{ $program->id }}"
                                                                tabindex="-1"
                                                                aria-labelledby="detailProgramLabel{{ $program->id }}"
                                                                aria-hidden="true">
                                                                <div class="modal-dialog modal-lg">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title"
                                                                                id="detailProgramLabel{{ $program->id }}">
                                                                                Detail Progres Kerja -
                                                                                {{ $program->deskripsi }}
                                                                            </h5>
                                                                            <button type="button" class="btn-close"
                                                                                data-bs-dismiss="modal"
                                                                                aria-label="Close"></button>
                                                                        </div>

                                                                        <div class="modal-body">
                                                                            <h6 class="fw-bold">Nama Pekerjaan</h6>
                                                                            <p>{{ $program->nama_pekerjaan ?? 'Belum nama pekerjaan.' }}
                                                                            </p>
                                                                            <hr>

                                                                            <h6 class="fw-bold">Penanggung Jawab</h6>
                                                                            <p>{{ $program->penanggung_jawab ?? 'Belum ditentukan.' }}
                                                                            </p>
                                                                            <hr>

                                                                            <h6 class="fw-bold">Vendor</h6>
                                                                            @if(!empty($program->vendor) && $program->vendor != 'Tidak Ada Vendor')
                                                                                <p>Jenis Vendor:
                                                                                    <strong>{{ $program->vendor }}</strong>
                                                                                </p>
                                                                                <p>Nama Vendor:
                                                                                    <strong>{{ $program->nama_vendor ?? '-' }}</strong>
                                                                                </p>

                                                                            @else
                                                                                <p>Tidak ada vendor.</p>
                                                                            @endif
                                                                            <hr>

                                                                            <h6 class="fw-bold">Kategori</h6>
                                                                            <p>{{ $program->kategori ?? '-' }}</p>
                                                                            <hr>

                                                                            <h6 class="fw-bold">Target Waktu</h6>
                                                                            <p>{{ \Carbon\Carbon::parse($program->target_waktu)->format('d-m-Y') ?? '-' }}
                                                                            </p>
                                                                            <hr>

                                                                            <h6 class="fw-bold">Progres Kerja</h6>
                                                                            @if($program->progressKerjas->count())
                                                                                <ul>
                                                                                    @foreach($program->progressKerjas as $progres)
                                                                                        <li>
                                                                                            <strong>{{ \Carbon\Carbon::parse($progres->tanggal)->format('d-m-Y') }}</strong>
                                                                                            –
                                                                                            {{ $progres->catatan }}
                                                                                        </li>
                                                                                    @endforeach
                                                                                </ul>

                                                                                {{-- 🔹 Tambahkan baris progres otomatis --}}
                                                                                <p class="mt-2">
                                                                                    <strong>Presentase:</strong>
                                                                                    @if($program->status === 'Selesai')
                                                                                        100%
                                                                                    @elseif($program->progressKerjas->last() && $program->progressKerjas->last()->persentase > 0)
                                                                                        {{ $program->progressKerjas->last()->persentase }}%
                                                                                    @else
                                                                                        -
                                                                                    @endif
                                                                                </p>
                                                                            @else
                                                                                <p class="text-muted">Belum ada progres yang
                                                                                    tercatat.
                                                                                </p>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- ========================= -->
                                                            <!-- 🧠 SCRIPT VENDOR + DANA -->
                                                            <!-- ========================= -->
                                                            <script>
                                                                document.addEventListener('DOMContentLoaded', function () {
                                                                    const id = "{{ $program->id }}";

                                                                    // ==========================
                                                                    // 🔹 Elemen untuk vendor
                                                                    // ==========================
                                                                    const vendorOption = document.getElementById(vendorOptionEdit${ id });
                                                                    const vendorDetailDiv = document.getElementById(vendorDetailDivEdit${ id });
                                                                    const vendorNameDiv = document.getElementById(vendorNameDivEdit${ id });
                                                                    const vendorTypeSelect = document.getElementById(vendorTypeEdit${ id });
                                                                    const vendorNameInput = document.getElementById(vendorNameEdit${ id });

                                                                    // Saat memilih ada/tidak ada vendor
                                                                    if (vendorOption) {
                                                                        vendorOption.addEventListener('change', function () {
                                                                            if (this.value === 'ada') {
                                                                                vendorDetailDiv.style.display = 'block';
                                                                                vendorNameDiv.style.display = 'block';
                                                                            } else {
                                                                                vendorDetailDiv.style.display = 'none';
                                                                                vendorNameDiv.style.display = 'none';
                                                                                vendorTypeSelect.value = '';
                                                                                vendorNameInput.value = '';
                                                                            }
                                                                        });
                                                                    }

                                                                    // ==========================
                                                                    // 🔹 Elemen untuk kategori & dana
                                                                    // ==========================
                                                                    const kategoriSelect = document.querySelector(#editModal${ id }.kategoriEdit);
                                                                    const danaWrapper = document.getElementById(danaWrapper${ id });
                                                                    const danaDisplay = document.getElementById(danaDisplay${ id });
                                                                    const danaHidden = document.getElementById(danaHidden${ id });

                                                                    // Format input dana otomatis
                                                                    if (danaDisplay) {
                                                                        danaDisplay.addEventListener('input', function () {
                                                                            let value = danaDisplay.value.replace(/\D/g, ''); // Hanya angka
                                                                            if (value === '') {
                                                                                danaDisplay.value = '';
                                                                                danaHidden.value = '';
                                                                                return;
                                                                            }
                                                                            danaDisplay.value = new Intl.NumberFormat('id-ID').format(value);
                                                                            danaHidden.value = value;
                                                                        });
                                                                    }

                                                                    // Sembunyikan input dana jika kategori = "Non-Finansial"
                                                                    function toggleDana() {
                                                                        if (kategoriSelect && kategoriSelect.value === 'Non-Finansial') {
                                                                            danaWrapper.style.display = 'none';
                                                                            danaHidden.value = 0;
                                                                        } else {
                                                                            danaWrapper.style.display = 'block';
                                                                        }
                                                                    }

                                                                    if (kategoriSelect) {
                                                                        kategoriSelect.addEventListener('change', toggleDana);
                                                                        toggleDana(); // Jalankan saat modal dibuka
                                                                    }
                                                                });
                                                            </script>
                                                        @endforeach
                                </tbody>
                            </table>
                        </div>

                        <style>
                            /* =========================
                            💠 RESPONSIVE TABEL & MODAL
                            ========================= */

                            /* Wrapper table */
                            .table-responsive {
                                overflow-x: auto;
                                -webkit-overflow-scrolling: touch;
                                border-radius: 10px;
                                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
                            }

                            /* Tabel dasar */
                            #programTable {
                                width: 100%;
                                min-width: 900px;
                                border-collapse: separate;
                                border-spacing: 0;
                                background: #fff;
                            }

                            #programTable thead th {
                                font-size: 14px;
                                text-transform: uppercase;
                                letter-spacing: 0.03em;
                                white-space: nowrap;
                            }

                            #programTable td {
                                vertical-align: middle;
                                white-space: nowrap;
                            }

                            /* Hover & border halus */
                            #programTable tr:hover {
                                background-color: #f8f9fa;
                                transition: background 0.2s ease;
                            }

                            /* Tombol kecil di tabel */
                            #programTable .btn {
                                font-size: 13px;
                                border-radius: 6px;
                                transition: all 0.25s ease-in-out;
                            }

                            #programTable .btn:hover {
                                transform: scale(1.05);
                            }

                            /* ===================================
                              📱 RESPONSIVE BEHAVIOR UNTUK MOBILE
                             =================================== */

                            /* Ketika layar < 768px */
                            @media (max-width: 768px) {
                                #programTable thead {
                                    display: none;
                                }

                                #programTable,
                                #programTable tbody,
                                #programTable tr,
                                #programTable td {
                                    display: block;
                                    width: 100%;
                                }

                                #programTable tr {
                                    background: #fff;
                                    margin-bottom: 12px;
                                    border-radius: 8px;
                                    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
                                    padding: 10px;
                                }

                                #programTable td {
                                    text-align: left;
                                    padding: 8px 12px;
                                    position: relative;
                                }

                                #programTable td::before {
                                    content: attr(data-label);
                                    display: block;
                                    font-weight: 600;
                                    color: #0d6efd;
                                    margin-bottom: 4px;
                                }

                                /* Tombol di bawah baris agar tidak bertumpuk */
                                #programTable td .btn {
                                    width: 100%;
                                    margin-top: 6px;
                                }

                                #programTable td.text-center {
                                    text-align: left !important;
                                }
                            }

                            /* ===================================
                               🪶 MODAL STYLING AGAR LEBIH HALUS
                            =================================== */

                            .modal-content {
                                border-radius: 14px;
                                border: none;
                                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
                            }

                            .modal-header {
                                background: linear-gradient(90deg, #0d6efd, #4c8efc);
                                color: #fff;
                                border-bottom: none;
                            }

                            .modal-title {
                                font-size: 16px;
                                font-weight: 600;
                            }

                            .modal-body {
                                font-size: 14px;
                                line-height: 1.6;
                            }

                            .modal-footer {
                                border-top: none;
                                background-color: #f8f9fa;
                            }

                            /* Efek transisi lembut */
                            .modal.fade .modal-dialog {
                                transform: translateY(-15px);
                                transition: transform 0.25s ease-out, opacity 0.25s ease-out;
                            }

                            .modal.show .modal-dialog {
                                transform: translateY(0);
                                opacity: 1;
                            }
                        </style>

                        <script>
                            // Tambahkan label otomatis agar versi mobile bisa menampilkan kolom
                            document.addEventListener("DOMContentLoaded", function () {
                                const headers = Array.from(document.querySelectorAll("#programTable thead th"))
                                    .map(th => th.textContent.trim());
                                document.querySelectorAll("#programTable tbody tr").forEach(row => {
                                    row.querySelectorAll("td").forEach((td, i) => {
                                        td.setAttribute("data-label", headers[i] || "");
                                    });
                                });
                            });
                        </script>

                        <script>
                            document.getElementById("filterKategori").addEventListener("change", function () {
                                let filter = this.value;

                                // Filter kotak detail anggaran
                                document.querySelectorAll(".anggaran-card").forEach(card => {
                                    let kategori = card.getAttribute("data-kategori");
                                    if (filter === "all" || kategori === filter) {
                                        card.style.display = "";
                                    } else {
                                        card.style.display = "none";
                                    }
                                });

                                // Filter tabel program kerja
                                document.querySelectorAll("tbody tr").forEach(row => {
                                    let kategori = row.getAttribute("data-kategori");

                                    // Kalau baris utama (punya data-kategori)
                                    if (kategori) {
                                        if (filter === "all" || kategori === filter) {
                                            row.style.display = "";
                                            // Pastikan baris detail terkait tetap ikut
                                            let detailId = row.querySelector("[data-bs-target]")?.getAttribute("data-bs-target");
                                            if (detailId) {
                                                let detailRow = document.querySelector(detailId);
                                                if (detailRow) detailRow.style.display = "";
                                            }
                                        } else {
                                            row.style.display = "none";
                                            // Sembunyikan baris detail terkait
                                            let detailId = row.querySelector("[data-bs-target]")?.getAttribute("data-bs-target");
                                            if (detailId) {
                                                let detailRow = document.querySelector(detailId);
                                                if (detailRow) detailRow.style.display = "none";
                                            }
                                        }
                                    }
                                });
                            });
                        </script>

                        {{-- ✅ Pagination kanan bawah (muncul hanya jika tidak sedang filter kategori) --}}
                        @if ($programKerjas instanceof \Illuminate\Pagination\LengthAwarePaginator)
                            <div class="d-flex justify-content-end mt-3">
                                {{ $programKerjas->links('pagination::bootstrap-5') }}
                            </div>
                        @endif

                        @php
                            $role = auth()->check() ? auth()->user()->role : null;
                        @endphp

                        <!-- Tombol Aksi -->
                        @if($role === 'admin')
                            <div class="d-flex justify-content-end gap-2 mt-3">

                                <!-- Tombol Cetak -->
                                <form action="{{ route('program-kerja.export.xls') }}" method="GET">
                                    <button type="submit" class="btn btn-success">
                                        Cetak
                                    </button>
                                </form>

                                <!-- Tombol buka modal tambah -->
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                                    + Tambah Program Baru
                                </button>

                            </div>
                        @endif

                        <!-- Modal Tambah Program Baru -->
                        <div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel"
                            aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <form action="{{ route('program-kerja.store') }}" method="POST">
                                        @csrf

                                        <!-- Tambahkan tahun filter agar ikut tersimpan -->
                                        <input type="hidden" name="tahun" value="{{ $tahun }}">

                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalTambahLabel">Tambah Program Kerja Baru</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">

                                            <!-- Nama Pekerjaan -->
                                            <div class="mb-3">
                                                <label class="form-label">Nama Pekerjaan</label>
                                                <input type="text" name="deskripsi" class="form-control" required>
                                            </div>

                                            <!-- Detail -->
                                            <div class="mb-3">
                                                <label class="form-label">Detail Pekerjaan</label>
                                                <textarea name="deskripsi_lengkap" class="form-control"
                                                    rows="3"></textarea>
                                            </div>

                                            <!-- Penanggung Jawab -->
                                            <div class="mb-3">
                                                <label class="form-label">Penanggung Jawab</label>
                                                <input type="text" name="penanggung_jawab" class="form-control"
                                                    required>
                                            </div>

                                            <!-- Vendor -->
                                            <div class="mb-3">
                                                <label class="form-label">Vendor</label>
                                                <select id="vendorOption" name="vendor_option" class="form-select"
                                                    required>
                                                    <option value="" disabled selected>Pilih Vendor</option>
                                                    <option value="tidak_ada">Tidak Ada Vendor</option>
                                                    <option value="ada">Ada Vendor</option>
                                                </select>
                                            </div>

                                            <!-- Jenis Vendor -->
                                            <div class="mb-3" id="vendorTypeDiv" style="display:none;">
                                                <label class="form-label">Jenis Vendor</label>
                                                <select name="vendor" id="vendorType" class="form-select">
                                                    <option value="" disabled selected>Pilih Jenis Vendor</option>
                                                    <option value="Vendor Eksternal">Vendor Eksternal</option>
                                                    <option value="Vendor Internal">Vendor Internal</option>
                                                    <option value="Vendor IT Subreg Jawa">Vendor IT Subreg Jawa</option>
                                                </select>
                                            </div>

                                            <!-- Nama Vendor -->
                                            <div class="mb-3" id="vendorNameDiv" style="display:none;">
                                                <label class="form-label">Nama Vendor</label>
                                                <input type="text" name="nama_vendor" id="vendorName"
                                                    class="form-control" placeholder="Masukkan nama vendor">
                                            </div>

                                            <!-- Waktu -->
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Waktu Dimulai</label>
                                                    <input type="date" name="waktu_dimulai" id="waktu_dimulai"
                                                        class="form-control" readonly>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Target Waktu</label>
                                                    <input type="date" name="target_waktu" id="target_waktu"
                                                        class="form-control" required>
                                                </div>
                                            </div>

                                            <!-- Kategori -->
                                            <div class="mb-3">
                                                <label class="form-label">Kategori</label>
                                                <select id="kategoriSelect" name="kategori" class="form-select"
                                                    required>
                                                    <option value="" disabled selected>Pilih Kategori</option>
                                                    @foreach ($anggarans as $anggaran)
                                                        <option value="{{ $anggaran->nama }}" data-id="{{ $anggaran->id }}"
                                                            data-total="{{ $anggaran->total }}"
                                                            data-tersisa="{{ $anggaran->tersisa }}">
                                                            {{ $anggaran->nama }}
                                                        </option>
                                                    @endforeach
                                                    <option value="Non-Finansial">Non-Finansial</option>
                                                </select>
                                            </div>

                                            <!-- Info Anggaran -->
                                            <div class="mb-3" id="anggaranWrapper">
                                                <label class="form-label">Anggaran (Total / Tersisa)</label>
                                                <input type="text" id="anggaranInput" class="form-control" readonly>
                                                <input type="hidden" name="anggaran_id" id="anggaranId">
                                            </div>

                                            <!-- Jenis Dana -->
                                            <div class="mb-3">
                                                <label class="form-label">Jenis Dana</label>
                                                <select id="danaOption" name="dana_option" class="form-select" required>
                                                    <option value="" disabled selected>Pilih Jenis Dana</option>
                                                    <option value="utama">Menggunakan Dana</option>
                                                    <option value="tidak">Tidak Menggunakan Dana</option>
                                                </select>
                                            </div>

                                            <!-- Dana Utama -->
                                            <div class="mb-3" id="danaUtamaWrapper" style="display:none;">
                                                <label class="form-label">Nominal Dana (Rp)</label>
                                                <input type="text" name="dana" class="form-control format-rupiah"
                                                    placeholder="Masukkan jumlah dana" required>
                                            </div>

                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary">Simpan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- SCRIPT (TIDAK ADA YANG DIUBAH) -->
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const waktuDimulai = document.getElementById("waktu_dimulai");
                                const targetWaktu = document.getElementById("target_waktu");
                                const today = new Date();
                                waktuDimulai.value = today.toISOString().split('T')[0];
                                const tomorrow = new Date(today);
                                tomorrow.setDate(today.getDate() + 1);
                                targetWaktu.min = tomorrow.toISOString().split('T')[0];

                                const kategoriSelect = document.getElementById('kategoriSelect');
                                const anggaranInput = document.getElementById('anggaranInput');
                                const anggaranId = document.getElementById('anggaranId');
                                const danaOption = document.getElementById('danaOption');
                                const danaUtamaWrapper = document.getElementById('danaUtamaWrapper');

                                kategoriSelect.addEventListener('change', function () {
                                    const selected = this.options[this.selectedIndex];
                                    anggaranInput.value = selected.dataset.total ?
                                        `Total: Rp ${selected.dataset.total} | Sisa: Rp ${selected.dataset.tersisa}` : '-';
                                    anggaranId.value = selected.dataset.id || '';
                                });

                                danaOption.addEventListener('change', function () {
                                    const inputDanaUtama = danaUtamaWrapper.querySelector('input[name="dana"]');
                                    danaUtamaWrapper.style.display = 'none';
                                    inputDanaUtama.removeAttribute('required');
                                    if (this.value === 'utama') {
                                        danaUtamaWrapper.style.display = 'block';
                                        inputDanaUtama.setAttribute('required', true);
                                    }
                                });

                                const rupiahInputs = document.querySelectorAll('.format-rupiah');
                                rupiahInputs.forEach(function (input) {
                                    input.addEventListener('input', function (e) {
                                        let value = e.target.value.replace(/\D/g, '');
                                        e.target.value = value ? new Intl.NumberFormat('id-ID').format(value) : '';
                                    });
                                    const form = input.closest('form');
                                    if (form) {
                                        form.addEventListener('submit', function () {
                                            input.value = input.value.replace(/\./g, '');
                                        });
                                    }
                                });

                                const vendorOption = document.getElementById('vendorOption');
                                const vendorTypeDiv = document.getElementById('vendorTypeDiv');
                                const vendorType = document.getElementById('vendorType');
                                const vendorNameDiv = document.getElementById('vendorNameDiv');
                                const vendorName = document.getElementById('vendorName');

                                vendorOption.addEventListener('change', function () {
                                    if (this.value === 'ada') {
                                        vendorTypeDiv.style.display = 'block';
                                        vendorType.required = true;
                                    } else {
                                        vendorTypeDiv.style.display = 'none';
                                        vendorNameDiv.style.display = 'none';
                                        vendorType.required = false;
                                        vendorName.required = false;
                                        vendorType.selectedIndex = 0;
                                        vendorName.value = '';
                                    }
                                });

                                vendorType.addEventListener('change', function () {
                                    if (this.value) {
                                        vendorNameDiv.style.display = 'block';
                                        vendorName.required = true;
                                    } else {
                                        vendorNameDiv.style.display = 'none';
                                        vendorName.required = false;
                                        vendorName.value = '';
                                    }
                                });
                            });
                        </script>


                        <!-- SweetAlert2 -->
                        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                        <!-- Chart.js -->
                        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

                        <script>
                            const kategori = @json($kategori);
                            const anggarans = @json($anggarans);
                            const programKerjas = @json($programKerjasAll);

                            // ===========================================
                            // HITUNG ANGKA OTOMATIS BERDASARKAN TABEL PROGRAM KERJA
                            // ===========================================
                            const total = kategori.map(k => anggarans[k]?.total ?? 0);

                            const terserap = kategori.map(k =>
                                programKerjas
                                    .filter(p => p.kategori === k)
                                    .reduce((sum, p) => sum + Number(p.dana), 0)
                            );

                            const tersisa = total.map((t, i) => t - terserap[i]);

                            // ===========================================
                            // UTILITAS FORMAT RUPIAH & MAX SCALE
                            // ===========================================
                            function roundUpToTenMillion(value) {
                                return Math.ceil(value / 10000000) * 10000000;
                            }

                            function formatRupiahSingkat(value) {
                                if (value >= 1_000_000_000) return (value / 1_000_000_000).toFixed(1).replace('.0', '') + 'M';
                                if (value >= 1_000_000) return (value / 1_000_000).toFixed(1).replace('.0', '') + 'JT';
                                if (value >= 1_000) return (value / 1_000).toFixed(1).replace('.0', '') + 'RB';
                                return value;
                            }

                            const fixedMax = roundUpToTenMillion(Math.max(...total, ...terserap, ...tersisa));

                            // ===========================================
                            // PLUGIN LABEL DI ATAS BATANG
                            // ===========================================
                            const dataLabelPlugin = {
                                id: 'dataLabelPlugin',
                                afterDatasetsDraw(chart) {
                                    const { ctx } = chart;
                                    chart.data.datasets.forEach((dataset, i) => {
                                        const meta = chart.getDatasetMeta(i);
                                        meta.data.forEach((bar, index) => {
                                            const value = dataset.data[index];
                                            if (value > 0) {
                                                ctx.fillStyle = "#000";
                                                ctx.font = "12px Arial";
                                                ctx.textAlign = "center";
                                                ctx.fillText("Rp " + formatRupiahSingkat(value), bar.x, bar.y - 5);
                                            }
                                        });
                                    });
                                }
                            };

                            // ===========================================
                            // DIAGRAM 100%
                            // ===========================================
                            const datasets100 = [
                                { label: 'Total Anggaran', data: total, backgroundColor: '#0d6efd' },
                                { label: 'Terserap', data: terserap, backgroundColor: '#198754' },
                                { label: 'Tersisa', data: tersisa, backgroundColor: '#ffc107' }
                            ];

                            const ctx100 = document.getElementById('barChart100');
                            const chart100 = new Chart(ctx100, {
                                type: 'bar',
                                data: { labels: kategori, datasets: datasets100 },
                                options: {
                                    responsive: true,
                                    animation: { duration: 1000, easing: 'easeOutCubic' },
                                    plugins: { legend: { position: 'bottom' } },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            max: fixedMax,
                                            ticks: {
                                                stepSize: 10000000,
                                                callback: value => 'Rp ' + formatRupiahSingkat(value)
                                            }
                                        }
                                    },
                                    onClick: (e, elements) => {
                                        if (elements.length === 0) return;

                                        const idx = elements[0].index;
                                        const namaKategori = kategori[idx];

                                        const totalVal = total[idx];
                                        const terserapVal = terserap[idx];
                                        const tersisaVal = tersisa[idx];

                                        const programList = programKerjas.filter(p => p.kategori === namaKategori);

                                        Swal.fire({
                                            title: `Detail Anggaran – ${namaKategori}`,
                                            html: `
                        <p><strong>Total:</strong> Rp ${formatRupiahSingkat(totalVal)}</p>
                        <p><strong>Terserap:</strong> Rp ${formatRupiahSingkat(terserapVal)}</p>
                        <p><strong>Tersisa:</strong> Rp ${formatRupiahSingkat(tersisaVal)}</p>
                        <hr>
                        <canvas id="popupChart100" style="width:100%;height:250px"></canvas>
                        <div id="tableContainer"></div>
                        <div id="paginationContainer" style="margin-top:10px;text-align:right;display:flex;justify-content:flex-end;align-items:center;gap:8px;"></div>
                    `,
                                            width: 700,
                                            didOpen: () => {
                                                // DONAT
                                                new Chart(document.getElementById("popupChart100"), {
                                                    type: "doughnut",
                                                    data: {
                                                        labels: ["Terserap", "Tersisa"],
                                                        datasets: [
                                                            {
                                                                data: [terserapVal, tersisaVal],
                                                                backgroundColor: ["#198754", "#ffc107"]
                                                            }
                                                        ]
                                                    },
                                                    options: {
                                                        plugins: { legend: { position: "bottom" } }
                                                    }
                                                });

                                                // TABEL PAGINASI
                                                const rowsPerPage = 5;
                                                let currentPage = 1;
                                                const totalPages = Math.ceil(programList.length / rowsPerPage);

                                                function renderTable(page) {
                                                    const start = (page - 1) * rowsPerPage;
                                                    const visible = programList.slice(start, start + rowsPerPage);

                                                    let html = "";

                                                    if (visible.length > 0) {
                                                        html = `
                                    <hr>
                                    <h6><strong>Detail Terserap:</strong></h6>
                                    <table style="width:100%;border-collapse:collapse;font-size:14px;text-align:center;">
                                        <thead>
                                            <tr style="background:#f1f1f1;">
                                                <th style="padding:8px;border:1px solid #dee2e6;">Nama Pekerjaan</th>
                                                <th style="padding:8px;border:1px solid #dee2e6;">Biaya</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${visible.map(p => `
                                                <tr>
                                                    <td style="padding:8px;border:1px solid #dee2e6;">${p.deskripsi}</td>
                                                    <td style="padding:8px;border:1px solid #dee2e6;">Rp ${Number(p.dana).toLocaleString('id-ID')}</td>
                                                </tr>
                                            `).join("")}
                                        </tbody>
                                    </table>
                                `;
                                                    } else {
                                                        html = `<p><em>Tidak ada data program.</em></p>`;
                                                    }

                                                    document.getElementById("tableContainer").innerHTML = html;
                                                    renderPagination();
                                                }

                                                function renderPagination() {
                                                    let html = "";

                                                    if (totalPages > 1) {
                                                        html = `
                                    <button id="prevPage" class="btn btn-sm btn-outline-secondary" ${currentPage === 1 ? "disabled" : ""}>«</button>
                                    <span style="font-size:14px;">Halaman ${currentPage} dari ${totalPages}</span>
                                    <button id="nextPage" class="btn btn-sm btn-outline-secondary" ${currentPage === totalPages ? "disabled" : ""}>»</button>
                                `;
                                                    }

                                                    const container = document.getElementById("paginationContainer");
                                                    container.innerHTML = html;

                                                    if (document.getElementById("prevPage"))
                                                        document.getElementById("prevPage").onclick = () => { currentPage--; renderTable(currentPage); };

                                                    if (document.getElementById("nextPage"))
                                                        document.getElementById("nextPage").onclick = () => { currentPage++; renderTable(currentPage); };
                                                }

                                                renderTable(currentPage);
                                            }
                                        });
                                    }
                                },
                                plugins: [dataLabelPlugin]
                            });

                            // ===========================================
                            // DIAGRAM 70% (DINAMIS)
                            // ===========================================
                            let chart70;

                            function renderChart(persen = 70) {
                                const faktor = persen / 100;

                                const datasetsX = [
                                    { label: 'Total Anggaran', data: total.map(t => Math.round(t * faktor)), backgroundColor: '#0d6efd' },
                                    { label: 'Terserap', data: terserap.map(t => Math.round(t * faktor)), backgroundColor: '#198754' },
                                    { label: 'Tersisa', data: tersisa.map(t => Math.round(t * faktor)), backgroundColor: '#ffc107' }
                                ];

                                if (chart70) chart70.destroy();

                                chart70 = new Chart(document.getElementById('barChart70'), {
                                    type: 'bar',
                                    data: { labels: kategori, datasets: datasetsX },
                                    options: {
                                        responsive: true,
                                        animation: { duration: 800, easing: "easeOutCubic" },
                                        plugins: { legend: { position: "bottom" } },
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                max: fixedMax,
                                                ticks: {
                                                    stepSize: 10000000,
                                                    callback: value => "Rp " + formatRupiahSingkat(value)
                                                }
                                            }
                                        }
                                    },
                                    plugins: [dataLabelPlugin]
                                });

                                document.getElementById("chartTitle").textContent = `Diagram Anggaran (${persen}%)`;
                            }

                            document.addEventListener("DOMContentLoaded", () => {
                                renderChart(70);
                                document.getElementById("filterPersen").value = 70;
                            });

                            document.getElementById("filterPersen").addEventListener("input", function () {
                                this.value = this.value.replace(/[^0-9]/g, "");
                                if (this.value > 100) this.value = 100;
                                if (this.value < 1) this.value = 1;
                            });

                            function updateChart() {
                                const persen = parseInt(document.getElementById("filterPersen").value);
                                renderChart(persen);
                            }
                        </script>
</body>

</html>
