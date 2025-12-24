<?php

namespace App\Http\Controllers;

use App\Models\{Anggaran, RiwayatDana, ProgramKerja, ArsipProgramKerja};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnggaranController extends Controller
{
    /**
     * ✅ Halaman utama anggaran + otomatis reset jika tahun baru dipilih
     */
    public function index(Request $request)
    {
        $tahun = (int) $request->input('tahun', date('Y')); // default tahun berjalan

        $kategori = [
            'Anggaran Pemeliharaan',
            'Anggaran Perlengkapan Perangkat',
            'Anggaran Komunikasi Data'
        ];

        // 🔹 Ambil data anggaran tahun yang dipilih
        $semuaAnggaran = Anggaran::where('tahun', $tahun)->get();

        // 🔸 Jika belum ada data untuk tahun tersebut → buat otomatis (reset awal)
        if ($semuaAnggaran->isEmpty()) {
            DB::transaction(function () use ($tahun, $kategori) {
                foreach ($kategori as $nama) {
                    Anggaran::create([
                        'nama' => $nama,
                        'total' => 0,
                        'terserap' => 0,
                        'tersisa' => 0,
                        'tahun' => $tahun,
                    ]);
                }
            });

            $semuaAnggaran = Anggaran::where('tahun', $tahun)->get();
        }

        $anggarans = $semuaAnggaran->keyBy('nama');

        return view('admin.dashboard', compact('kategori', 'anggarans', 'semuaAnggaran', 'tahun'));
    }

    /**
     * ✅ Tambah dana ke anggaran tertentu
     */
    public function tambahDana(Request $request, $id)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jumlah' => 'required|numeric|min:1',
            'sumber' => 'nullable|string',
            'keterangan' => 'nullable|string',
            'deskripsi' => 'nullable|string',
        ]);

        $anggaran = Anggaran::findOrFail($id);

        DB::transaction(function () use ($anggaran, $request) {
            $anggaran->update([
                'total' => $anggaran->total + $request->jumlah,
                'tersisa' => $anggaran->tersisa + $request->jumlah,
            ]);

            RiwayatDana::create([
                'anggaran_id' => $anggaran->id,
                'tanggal' => $request->tanggal,
                'jumlah' => $request->jumlah,
                'sumber' => $request->sumber,
                'keterangan' => $request->keterangan ?? '-',
                'deskripsi' => $request->deskripsi ?? '-',
            ]);
        });

        return back()->with('success', 'Tambahan dana berhasil disimpan!');
    }

    /**
     * ✅ Transfer dana antar-anggaran
     */
    public function transferDana(Request $request, $id)
    {
        $request['jumlah_transfer'] = preg_replace('/[^\d]/', '', $request->jumlah_transfer);

        $request->validate([
            'tujuan' => 'required|numeric|exists:anggarans,id',
            'jumlah_transfer' => 'required|numeric|min:1',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
        ]);

        $asal = Anggaran::findOrFail($id);
        $tujuan = Anggaran::findOrFail($request->tujuan);
        $jumlah = (int) $request->jumlah_transfer;

        if ($asal->tersisa < $jumlah) {
            return back()->with('error', 'Dana tersisa tidak mencukupi!');
        }

        DB::transaction(function () use ($asal, $tujuan, $jumlah, $request) {
            // 🔹 Update saldo
            $asal->decrement('total', $jumlah);
            $asal->decrement('tersisa', $jumlah);
            $tujuan->increment('total', $jumlah);
            $tujuan->increment('tersisa', $jumlah);

            // 🔹 Catat riwayat
            RiwayatDana::create([
                'anggaran_id' => $asal->id,
                'tanggal' => $request->tanggal,
                'jumlah' => -$jumlah,
                'sumber' => "Transfer ke {$tujuan->nama}",
                'keterangan' => $request->keterangan ?? '-',
                'deskripsi' => "Transfer keluar ke {$tujuan->nama}",
            ]);

            RiwayatDana::create([
                'anggaran_id' => $tujuan->id,
                'tanggal' => $request->tanggal,
                'jumlah' => $jumlah,
                'sumber' => "Transfer dari {$asal->nama}",
                'keterangan' => $request->keterangan ?? '-',
                'deskripsi' => "Dana diterima dari {$asal->nama}",
            ]);

            Log::info("Transfer dana: {$jumlah} dari {$asal->nama} ke {$tujuan->nama}");
        });

        return back()->with('success', 'Transfer dana berhasil!');
    }

    /**
     * ✅ Tambah anggaran baru manual
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'total' => 'required|numeric|min:0',
            'tahun' => 'required|integer',
        ]);

        // Cegah duplikasi nama anggaran dalam tahun yang sama
        $existing = Anggaran::where('nama', $request->nama)
            ->where('tahun', $request->tahun)
            ->first();

        if ($existing) {
            return back()->with('error', 'Anggaran dengan nama tersebut sudah ada pada tahun ini!');
        }

        Anggaran::create([
            'nama' => $request->nama,
            'total' => $request->total,
            'terserap' => 0,
            'tersisa' => $request->total,
            'tahun' => $request->tahun,  // ambil dari input, bukan date('Y')
        ]);

        return back()->with('success', 'Anggaran baru berhasil ditambahkan!');
    }

    /**
     * ✅ Reset ke tahun baru (arsipkan & kosongkan data)
     */
    public function resetTahunBaru(Request $request)
    {
        $tahunSekarang = (int) $request->input('tahun_sekarang');
        $tahunBaru = (int) $request->input('tahun_baru');

        if ($tahunBaru < 2026) {
            return back()->with('info', 'Reset hanya berlaku mulai tahun 2026.');
        }

        DB::beginTransaction();
        try {
            // Arsipkan program kerja lama
            $programLama = ProgramKerja::where('tahun', $tahunSekarang)->get();

            foreach ($programLama as $p) {
                ArsipProgramKerja::create([
                    'nama_program' => $p->deskripsi ?? 'Tanpa Nama',
                    'status' => $p->status ?? 'Belum Dimulai',
                    'tahun' => $tahunSekarang,
                ]);
            }

            // Hapus program & anggaran lama
            ProgramKerja::where('tahun', $tahunSekarang)->delete();
            Anggaran::where('tahun', $tahunSekarang)->delete();

            // Kosongkan riwayat dana
            RiwayatDana::truncate();

            // Buat anggaran baru untuk tahun baru
            $kategori = [
                'Anggaran Pemeliharaan',
                'Anggaran Perlengkapan Perangkat',
                'Anggaran Komunikasi Data'
            ];

            foreach ($kategori as $nama) {
                Anggaran::create([
                    'nama' => $nama,
                    'total' => 0,
                    'terserap' => 0,
                    'tersisa' => 0,
                    'tahun' => $tahunBaru,
                ]);
            }

            DB::commit();
            return back()->with('success', "Reset tahun $tahunBaru berhasil! Semua data lama telah diarsipkan dan data tahun baru siap.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat reset: ' . $e->getMessage());
        }
    }
}
