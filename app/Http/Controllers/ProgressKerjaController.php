<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProgressKerja;
use App\Models\ProgramKerja;
use Illuminate\Support\Facades\Storage;

class ProgressKerjaController extends Controller
{
    /**
     * SIMPAN PROGRES BARU
     */
    public function store(Request $request)
    {
        $request->validate([
            'program_kerja_id' => 'required|exists:program_kerjas,id',
            'tanggal' => 'required|date',
            'catatan' => 'required|string',
            'status' => 'required|string|in:Belum Selesai,Selesai',
            'bukti_dokumen' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);

        // SIMPAN PROGRES
        $data = [
            'program_kerja_id' => $request->program_kerja_id,
            'tanggal' => $request->tanggal,
            'catatan' => $request->catatan,
            'status' => $request->status,
            'status_verifikasi' => 'pending',
        ];

        if ($request->hasFile('bukti_dokumen')) {
            $data['bukti_dokumen'] = $request->file('bukti_dokumen')->store('bukti_dokumen', 'public');
        }

        ProgressKerja::create($data);

        // UPDATE STATUS PROGRAM
        $program = ProgramKerja::findOrFail($request->program_kerja_id);

        if ($request->status === 'Selesai') {
            $program->status = 'Menunggu Approval Manager';
        } else {
            $program->status = 'Sedang Dikerjakan';
        }

        $program->save();

        return back()->with('success', 'Progres berhasil disimpan.');
    }

    /**
     * TAMBAH PROGRES BARU MELALUI UPDATE (TIDAK MENGUPDATE DATA LAMA)
     */
    public function update(Request $request, $id)
    {
        $program = ProgramKerja::findOrFail($id);

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'catatan' => 'required|string',
            'status' => 'required|string|in:Belum Selesai,Selesai',
            'bukti_dokumen' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);

        // BUAT PROGRES BARU
        $progress = new ProgressKerja();
        $progress->program_kerja_id = $program->id;
        $progress->tanggal = $validated['tanggal'];
        $progress->catatan = $validated['catatan'];
        $progress->status = $validated['status'];
        $progress->status_verifikasi = 'pending';

        if ($request->hasFile('bukti_dokumen')) {
            $progress->bukti_dokumen = $request->file('bukti_dokumen')->store('bukti_dokumen', 'public');
        }

        $progress->save();

        // UPDATE STATUS PROGRAM
        if ($validated['status'] === 'Selesai') {
            $program->status = 'Menunggu Approval Manager';
        } else {
            $program->status = 'Sedang Dikerjakan';
        }

        $program->save();

        return back()->with('success', 'Progres berhasil ditambahkan.');
    }

    /**
     * MANAGER APPROVE PENYELESAIAN
     */
    public function approveProgress($id)
    {
        $progress = ProgressKerja::findOrFail($id);
        $progress->status_verifikasi = 'approved';
        $progress->save();

        $program = ProgramKerja::findOrFail($progress->program_kerja_id);
        $program->status = 'Selesai';
        $program->save();

        return back()->with('success', 'Progress telah disetujui dan program dinyatakan selesai.');
    }

    /**
     * HAPUS PROGRES
     */
    public function destroy($id)
    {
        $progress = ProgressKerja::findOrFail($id);

        if ($progress->bukti_dokumen && Storage::disk('public')->exists($progress->bukti_dokumen)) {
            Storage::disk('public')->delete($progress->bukti_dokumen);
        }

        $progress->delete();

        return back()->with('success', 'Data progress berhasil dihapus.');
    }
}
