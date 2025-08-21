<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ExamController;
use App\Models\ExamSession;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Di sini Anda dapat mendaftarkan rute web untuk aplikasi Anda.
| Rute-rute ini dimuat oleh RouteServiceProvider dalam grup yang
| berisi grup middleware "web".
|
*/

// Route untuk halaman depan (Welcome Page)
Route::get('/', function () {
    // Untuk aplikasi Inertia, lebih baik redirect ke login jika belum terautentikasi
    // atau ke dashboard jika sudah login. Namun, menampilkan halaman selamat datang juga tidak masalah.
    return view('welcome');
});

// Route untuk Dashboard, hanya bisa diakses setelah login
Route::get('/dashboard', function () {
    // Cek apakah siswa punya sesi ujian yang statusnya 'ongoing'
    $activeSession = ExamSession::where('student_id', Auth::id())
        ->where('status', 'ongoing')
        ->first();

    // Jika ada sesi aktif, langsung arahkan ke halaman ujian
    if ($activeSession) {
        return redirect()->route('exam.show', $activeSession->id);
    }

    // Jika tidak ada, tampilkan halaman Dashboard menggunakan Inertia
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


// Grup route yang memerlukan autentikasi (siswa harus login)
Route::middleware('auth')->group(function () {
    
    // Route untuk mengelola profil siswa (dibuat oleh Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Route untuk Simulasi Ujian
    |--------------------------------------------------------------------------
    | Semua route yang berhubungan dengan proses ujian ada di sini.
    */
    
    // 1. MEMULAI sesi ujian baru
    Route::post('/exam/start', [ExamController::class, 'start'])->name('exam.start');

    // 2. MENAMPILKAN halaman simulasi ujian (terminal & instruksi)
    Route::get('/exam/{session}', [ExamController::class, 'show'])->name('exam.show');

    // 3. MENGEKSEKUSI perintah dari terminal (endpoint untuk Vue)
    Route::post('/exam/execute', [ExamController::class, 'execute'])->name('exam.execute');

    // 4. MENYIMPAN konten dari editor teks (endpoint untuk Vue)
    Route::post('/exam/submit-config', [ExamController::class, 'submitConfig'])->name('exam.submitConfig');
    
    // 5. MENYELESAIKAN ujian dan memproses hasil
    Route::post('/exam/finalize', [ExamController::class, 'finalize'])->name('exam.finalize');

    // 6. MENAMPILKAN halaman hasil ujian
    Route::get('/exam/result/{session}', [ExamController::class, 'result'])->name('exam.result');

});


// Ini akan memuat semua route autentikasi dari Laravel Breeze
// (halaman login, register, logout, dll.)
require __DIR__.'/auth.php';