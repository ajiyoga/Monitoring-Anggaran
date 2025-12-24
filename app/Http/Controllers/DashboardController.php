<?php

namespace App\Http\Controllers;

use App\Models\Anggaran;
use App\Models\ProgramKerja;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Gunakan tahun dipilih, default tahun sekarang
        $tahun = $request->get('tahun', date('Y'));

        // FILTER ANGGARAN
        $anggarans = Anggaran::with('details')
            ->where('tahun', $tahun) // sesuaikan jika pakai kolom lain
            ->get();

        // FILTER PROGRAM KERJA
        $programKerjas = ProgramKerja::with('progress')
            ->where('tahun', $tahun) // sesuaikan jika pakai kolom lain
            ->get();

        // DATA UNTUK CHART
        $labels = [];
        $total = [];
        $terserap = [];

        foreach ($anggarans as $item) {
            $labels[] = $item->kategori ?? 'Tanpa Kategori';
            $total[] = $item->total ?? 0;
            $terserap[] = $item->terserap ?? 0;
        }

        return view('dashboard.index', compact(
            'anggarans',
            'programKerjas',
            'labels',
            'total',
            'terserap',
            'tahun'
        ));
    }

}
