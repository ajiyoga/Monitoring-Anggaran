<?php

namespace App\Http\Controllers;

use App\Models\ProgramKerja;
use App\Models\Anggaran;
use Illuminate\Http\Request;
use App\Models\ArsipProgramKerja;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProgramKerjaController extends Controller
{
    /**
     *Dashboard — Filter berdasarkan tahun program, bukan created_at
     */
    public function index(Request $request)
    {
        // Tahun dipilih / default tahun berjalan
        $tahun = $request->input('tahun', date('Y'));
        $selectedYear = $tahun;

        /**
         * Update otomatis status program yang melewati target waktu
         */
        ProgramKerja::where('status', '!=', 'Selesai')
            ->whereDate('target_waktu', '<', now()->toDateString())
            ->update(['status' => 'Belum Selesai']);

        /**
         * Query utama (tanpa pagination dulu)
         */
        $query = ProgramKerja::with(['progressKerjas', 'anggaran.riwayatDana'])
            ->where('tahun', $selectedYear)
            ->orderBy('id', 'desc');

        /**
         * Filter kategori
         */
        $isFilteredKategori = false;
        if ($request->filled('filterKategori') && $request->filterKategori !== 'all') {
            $query->where('kategori', $request->filterKategori);
            $isFilteredKategori = true;  // → untuk menentukan apakah pagination ditampilkan
        }

        /**
         * Pencarian
         */
        if ($request->filled('search')) {
            $search = str_replace('.', '', $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('deskripsi', 'like', "%$search%")
                    ->orWhere('deskripsi_lengkap', 'like', "%$search%")
                    ->orWhere('penanggung_jawab', 'like', "%$search%")
                    ->orWhere('vendor', 'like', "%$search%")
                    ->orWhere('nama_vendor', 'like', "%$search%")
                    ->orWhere('kategori', 'like', "%$search%")
                    ->orWhere('waktu_dimulai', 'like', "%$search%")
                    ->orWhere('target_waktu', 'like', "%$search%")
                    ->orWhere('status', 'like', "%$search%")
                    ->orWhereRaw("CAST(dana AS CHAR) LIKE '%$search%'");
            });
        }

        /**
         * Pagination
         * - Jika kategori terfilter → tampil semua tanpa paginate
         * - Jika tidak filter → paginate 5
         */
        if ($isFilteredKategori) {
            $programKerjas = $query->get(); // TANPA pagination
            $pagination = false;
        } else {
            $programKerjas = $query->paginate(5)
                ->appends($request->only('search', 'filterKategori', 'tahun'));
            $pagination = true;
        }

        /**
         * Data grafik berdasarkan tahun program
         */
        $programKerjasAll = ProgramKerja::select('kategori', 'deskripsi', 'dana')
            ->where('tahun', $selectedYear)
            ->get();

        // Daftar kategori anggaran
        $kategori = [
            'Anggaran Pemeliharaan',
            'Anggaran Perlengkapan Perangkat',
            'Anggaran Komunikasi Data',
        ];

        /**
         * Anggaran sesuai tahun
         */
        $semuaAnggaran = Anggaran::where('tahun', $selectedYear)->get();
        $anggarans = $semuaAnggaran->keyBy('nama');

        /**
         * Hitung status berdasarkan tahun program
         */
        $totalProgram = ProgramKerja::where('tahun', $selectedYear)->count();
        $programProses = ProgramKerja::where('tahun', $selectedYear)->where('status', 'Sedang Dikerjakan')->count();
        $programBelumSelesai = ProgramKerja::where('tahun', $selectedYear)->where('status', 'Belum Selesai')->count();
        $programSelesai = ProgramKerja::where('tahun', $selectedYear)->where('status', 'Selesai')->count();
        $programBaru = ProgramKerja::where('tahun', $selectedYear)->where('status', 'Menunggu Validasi Manajer')->count();

        /**
         * List tahun (current year → +5)
         */
        $years = range(date('Y'), date('Y') + 5);

        return view('dashboard.index', compact(
            'programKerjas',
            'programKerjasAll',
            'anggarans',
            'kategori',
            'semuaAnggaran',
            'totalProgram',
            'programProses',
            'programBelumSelesai',
            'programSelesai',
            'programBaru',
            'tahun',
            'years',
            'selectedYear',
            'pagination'   // untuk blade nantinya
        ));
    }

    /**
     * 🔹 Simpan program kerja baru (Fix: tahun mengikuti target waktu)
     */
    public function store(Request $request)
    {
        $request->validate([
            'deskripsi' => 'required|string|max:255',
            'deskripsi_lengkap' => 'nullable|string',
            'penanggung_jawab' => 'required|string|max:100',
            'target_waktu' => 'required|date|after:today',
            'kategori' => 'required|string',
            'anggaran_id' => 'nullable|exists:anggarans,id',
            'anggaran_id_tambahan' => 'nullable|exists:anggarans,id',
            'riwayat_dana_id' => 'nullable|exists:riwayat_danas,id',
            'dana_option' => 'required|string|in:utama,tambahan,tidak',
            'dana' => 'nullable|numeric|min:0',
            'dana_tambahan_dipakai' => 'nullable|numeric|min:0',
            'vendor' => 'nullable|string|max:255',
            'nama_vendor' => 'nullable|string|max:255',
        ]);

        $isTambahan = $request->dana_option === 'tambahan';
        $isUtama = $request->dana_option === 'utama';

        $anggaranId = $isTambahan ? $request->anggaran_id_tambahan : ($isUtama ? $request->anggaran_id : null);
        $riwayatDanaId = $isTambahan ? $request->riwayat_dana_id : null;
        $dana = $isTambahan
            ? ($request->dana_tambahan_dipakai ?? 0)
            : ($isUtama ? ($request->dana ?? 0) : 0);

        if ($anggaranId && $dana > 0) {
            $anggaran = Anggaran::find($anggaranId);

            if (!$anggaran) {
                return back()->withErrors(['anggaran_id' => 'Data anggaran tidak ditemukan.']);
            }

            if ($anggaran->tersisa < $dana) {
                return back()->withErrors(['dana' => 'Dana yang digunakan melebihi sisa anggaran.']);
            }
        }

        $program = ProgramKerja::create([
            'deskripsi' => $request->deskripsi,
            'deskripsi_lengkap' => $request->deskripsi_lengkap,
            'penanggung_jawab' => $request->penanggung_jawab,
            'vendor' => $request->vendor ?? 'Tidak Ada Vendor',
            'nama_vendor' => $request->nama_vendor ?? '-',
            'waktu_dimulai' => now(),
            'target_waktu' => $request->target_waktu,
            'status' => 'Menunggu Validasi Manajer',
            'kategori' => $request->kategori,
            'anggaran_id' => $anggaranId,
            'riwayat_dana_id' => $riwayatDanaId,
            'dana' => $dana,
            'dana_tambahan_dipakai' => $isTambahan ? $request->dana_tambahan_dipakai : 0,
            'tahun' => date('Y', strtotime($request->target_waktu)),
        ]);

        if ($anggaranId && $dana > 0) {
            $anggaran->terserap += $dana;
            $anggaran->tersisa = max($anggaran->total - $anggaran->terserap, 0);
            $anggaran->save();
        }

        // --- PERBAIKAN AGAR FILTER TAHUN TIDAK HILANG ---
        $tahunDipilih = $request->input('tahun', date('Y'));

        return $this->redirectByRole(
            'Program kerja baru berhasil ditambahkan dan menunggu validasi manajer.',
            $tahunDipilih
        );
    }


    /**
     * 🔹 Validasi (manajer)
     */
    public function approve($id)
    {
        $program = ProgramKerja::findOrFail($id);

        if (Auth::user()->role === 'manajer') {

            if ($program->status === 'Menunggu Validasi Manajer') {
                $program->status = 'Proses';
            } elseif ($program->status === 'Menunggu Approval Manajer') {
                $program->status = 'Selesai';
            }

            $program->approved = true;
            $program->save();

            return back()->with('success', 'Program kerja berhasil divalidasi!');
        }

        abort(403);
    }

    /**
     * 🔹 Update program kerja
     */
    public function update(Request $request, $id)
    {
        $program = ProgramKerja::findOrFail($id);
        $danaLama = $program->dana ?? 0;

        $request->validate([
            'deskripsi' => 'required|string|max:255',
            'deskripsi_lengkap' => 'nullable|string',
            'penanggung_jawab' => 'required|string|max:100',
            'target_waktu' => 'required|date|after:today',
            'kategori' => 'required|string',
            'anggaran_id' => 'nullable|exists:anggarans,id',
            'dana' => 'nullable|numeric|min:0',
            'vendor' => 'nullable|string|max:255',
            'nama_vendor' => 'nullable|string|max:255',
            'status' => 'required|string',
        ]);

        $danaBaru = $request->dana ?? 0;

        $program->update($request->only([
            'deskripsi',
            'deskripsi_lengkap',
            'penanggung_jawab',
            'target_waktu',
            'kategori',
            'anggaran_id',
            'vendor',
            'nama_vendor',
            'status'
        ]) + [
            'dana' => $danaBaru,
            'tahun' => date('Y', strtotime($request->target_waktu)),
        ]);

        if ($program->anggaran_id) {
            $anggaran = Anggaran::find($program->anggaran_id);

            if ($anggaran) {
                $selisih = $danaBaru - $danaLama;
                $anggaran->terserap = max(min($anggaran->terserap + $selisih, $anggaran->total), 0);
                $anggaran->tersisa = max($anggaran->total - $anggaran->terserap, 0);
                $anggaran->save();
            }
        }

        // --- PERBAIKAN ---
        $tahunDipilih = $request->input('tahun', date('Y'));

        return $this->redirectByRole('Program kerja berhasil diperbarui!', $tahunDipilih);
    }


    /**
     * Hapus program kerja
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['admin', 'manajer'])) {
            abort(403);
        }

        $program = ProgramKerja::findOrFail($id);

        if ($program->anggaran_id) {
            $anggaran = Anggaran::find($program->anggaran_id);

            if ($anggaran) {
                $anggaran->terserap = max($anggaran->terserap - $program->dana, 0);
                $anggaran->tersisa = max($anggaran->total - $anggaran->terserap, 0);
                $anggaran->save();
            }
        }

        $program->delete();

        return $this->redirectByRole('Program kerja berhasil dihapus!');
    }

    /**
     * Redirect sesuai role
     */
    private function redirectByRole($message, $tahun = null)
    {
        $role = Auth::user()->role ?? 'user';

        $params = [];
        if ($tahun) {
            $params['tahun'] = $tahun;
        }

        return match ($role) {
            'admin' => redirect()->route('admin.dashboard', $params)->with('success', $message),
            'manajer' => redirect()->route('manajer.dashboard', $params)->with('success', $message),
            default => redirect()->route('user.dashboard', $params)->with('success', $message),
        };
    }


    /**
     * Tandai selesai (user)
     */
    public function markAsCompleted($id)
    {
        $program = ProgramKerja::findOrFail($id);

        if ($program->status !== 'Proses') {
            return back()->with('error', 'Program kerja belum bisa ditandai selesai.');
        }

        $program->status = 'Menunggu Approval Manajer';
        $program->save();

        return back()->with('success', 'Program kerja menunggu persetujuan manajer.');
    }

    /**
     * Validasi program
     */
    public function validateProgram($id)
    {
        $user = Auth::user();
        $program = ProgramKerja::findOrFail($id);

        if ($user->role === 'admin') {
            $program->status = 'Menunggu Validasi Manajer';
        } elseif ($user->role === 'manajer') {
            $program->status = $program->status === 'Menunggu Validasi Manajer'
                ? 'Proses'
                : 'Tervalidasi';
        }

        $program->save();

        return back()->with('success', 'Program kerja berhasil divalidasi.');
    }

    /**
     * Detail program kerja (FIX: tidak mengembalikan dashboard)
     */
    public function show($id)
    {
        $program = ProgramKerja::with(['progressKerjas', 'anggaran'])->findOrFail($id);
        return view('dashboard.index', compact('program'));
    }

    /**
     * Reset tahun baru
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
            // Arsip program tahun berjalan
            $programLama = ProgramKerja::where('tahun', $tahunSekarang)->get();

            if ($programLama->isEmpty()) {
                return back()->with('warning', 'Tidak ada data program kerja tahun ini.');
            }

            foreach ($programLama as $program) {
                ArsipProgramKerja::create([
                    'nama_program' => $program->deskripsi,
                    'anggaran_total' => $program->dana,
                    'anggaran_terserap' => $program->dana,
                    'status' => $program->status,
                    'tahun' => $tahunSekarang,
                ]);
            }

            // Hapus program berjalan
            ProgramKerja::where('tahun', $tahunSekarang)->delete();

            // Arsipkan anggaran
            $anggaranLama = Anggaran::where('tahun', $tahunSekarang)->get();

            foreach ($anggaranLama as $a) {
                DB::table('arsip_anggarans')->insert([
                    'nama' => $a->nama,
                    'total' => $a->total,
                    'terserap' => $a->terserap,
                    'tersisa' => $a->tersisa,
                    'tahun' => $tahunSekarang,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Hapus anggaran tahun lama
            Anggaran::where('tahun', $tahunSekarang)->delete();

            // Tambahkan anggaran kosong untuk tahun baru
            $kategori = [
                'Anggaran Pemeliharaan',
                'Anggaran Perlengkapan Perangkat',
                'Anggaran Komunikasi Data',
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

            // Kosongkan tabel riwayat dana
            \App\Models\RiwayatDana::truncate();

            DB::commit();

            return back()->with('success', "Reset tahun $tahunBaru berhasil.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Export XLS
     */
    public function exportXLS()
    {
        $filename = 'program_kerja_' . date('Ymd_His') . '.xls';

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        $programs = ProgramKerja::orderBy('tahun')->orderBy('id')->get();

        echo "<table border='1'>";
        echo "<thead>
        <tr style='background-color:#f2f2f2; font-weight:bold;'>
            <th>Nama Pekerjaan</th>
            <th>Penanggung Jawab</th>
            <th>Vendor</th>
            <th>Waktu Dimulai</th>
            <th>Target Waktu</th>
            <th>Status</th>
            <th>Kategori</th>
            <th>Biaya (Rp)</th>
            <th>Tahun</th>
        </tr>
        </thead><tbody>";

        foreach ($programs as $program) {
            echo "<tr>
                <td>{$program->deskripsi}</td>
                <td>{$program->penanggung_jawab}</td>
                <td>{$program->vendor}</td>
                <td>{$program->waktu_dimulai}</td>
                <td>{$program->target_waktu}</td>
                <td>{$program->status}</td>
                <td>{$program->kategori}</td>
                <td>" . number_format($program->dana, 0, ',', '.') . "</td>
                <td>{$program->tahun}</td>
            </tr>";
        }

        echo "</tbody></table>";
        exit;
    }
}
