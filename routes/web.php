<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\{
    LoginController,
    ProgramKerjaController,
    ProgressKerjaController,
    AnggaranController
};

/*
|--------------------------------------------------------------------------
| Redirect Halaman Utama
|--------------------------------------------------------------------------
*/
Route::get('/', fn() => redirect()->route('login.admin'));

/*
|--------------------------------------------------------------------------
| LOGIN & LOGOUT
|--------------------------------------------------------------------------
*/
Route::get('/login-admin', [LoginController::class, 'showAdminLoginForm'])->name('login.admin');
Route::get('/login-manajer', [LoginController::class, 'showManajerLoginForm'])->name('login.manajer');
Route::get('/login-user', [LoginController::class, 'showUserLoginForm'])->name('login.user');

Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.post');

/*
|--------------------------------------------------------------------------
| ROUTE ADMIN
|--------------------------------------------------------------------------
*/
Route::middleware(['role:admin'])->group(function () {

    Route::get('/admin/dashboard', [ProgramKerjaController::class, 'index'])->name('admin.dashboard');

    // CRUD Program Kerja
    Route::resource('program-kerja', ProgramKerjaController::class);

    // CRUD Anggaran (khusus admin)
    Route::resource('anggaran', AnggaranController::class);

    /*
    |--------------------------------------------------------------------------
    | ROUTE UPDATE PROGRES (KHUSUS PER PROGRAM KERJA) → PERBAIKAN
    |--------------------------------------------------------------------------
    |
    | Ini penting karena progress.update harus memakai program_id,
    | bukan id progres. Jika tidak ada ini maka muncul 404.
    |
    */
    Route::post(
        '/progress-kerja/{program_id}/update',
        [ProgressKerjaController::class, 'update']
    )->name('progress.update');

    // CRUD Progress Kerja standar
    Route::resource('progres', ProgressKerjaController::class)->except(['create', 'edit']);

    // Tambah & Transfer Dana
    Route::post('/anggaran/{id}/tambah-dana', [AnggaranController::class, 'tambahDana'])
        ->name('anggaran.tambahDana');

    Route::post('/anggaran/{id}/transfer-dana', [AnggaranController::class, 'transferDana'])
        ->name('anggaran.transferDana');

    // Filter Tahun
    Route::get('/filter-tahun', [ProgramKerjaController::class, 'filterTahun'])
        ->name('filter.tahun');

    // Ekspor XLS
    Route::get('/program-kerja/export/xls', [ProgramKerjaController::class, 'exportXLS'])
        ->name('program-kerja.export.xls');
});

/*
|--------------------------------------------------------------------------
| ROUTE MANAJER
|--------------------------------------------------------------------------
*/
Route::middleware(['role:manajer'])->group(function () {

    Route::get('/manajer/dashboard', [ProgramKerjaController::class, 'index'])->name('manajer.dashboard');

    // Approval Program Kerja
    Route::post('/program-kerja/{id}/approve', [ProgramKerjaController::class, 'approve'])->name('program-kerja.approve');
    Route::post('/program-kerja/{id}/validate', [ProgramKerjaController::class, 'validateProgram'])->name('program-kerja.validate');

    // Approval Progress
    Route::post('/progres/{id}/approve', [ProgressKerjaController::class, 'approveProgress'])->name('progres.approve');
    Route::post('/progres/{id}/reject', [ProgressKerjaController::class, 'rejectProgress'])->name('progres.reject');
});

/*
|--------------------------------------------------------------------------
| IZINKAN ADMIN & MANAJER MENGHAPUS PROGRAM KERJA (OVERWRITE DELETE)
|--------------------------------------------------------------------------
*/
Route::delete('/programkerja/{id}', [ProgramKerjaController::class, 'destroy'])
    ->middleware('role:admin,manajer')
    ->name('programkerja.destroy');

/*
|--------------------------------------------------------------------------
| ROUTE USER
|--------------------------------------------------------------------------
*/
Route::middleware(['role:user'])->group(function () {
    Route::get('/user/dashboard', [ProgramKerjaController::class, 'index'])->name('user.dashboard');
});
