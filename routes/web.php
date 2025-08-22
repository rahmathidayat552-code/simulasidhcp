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
*/

Route::get('/', function () {
    // Menampilkan halaman Welcome dari Vue
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::get('/dashboard', function () {
    // Cek apakah ada sesi ujian yang sedang berlangsung
    $activeSession = ExamSession::where('student_id', Auth::id())
        ->where('status', 'ongoing')
        ->first();

    // Jika ada, langsung redirect ke halaman ujian
    if ($activeSession) {
        return redirect()->route('exam.show', $activeSession->id);
    }

    // Jika tidak, tampilkan dashboard
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Grup route yang hanya bisa diakses setelah login
Route::middleware('auth')->group(function () {
    // Route untuk profil (bawaan Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*--- Route untuk Simulasi Ujian ---*/
    Route::post('/exam/start', [ExamController::class, 'start'])->name('exam.start');
    Route::get('/exam/{session}', [ExamController::class, 'show'])->name('exam.show');
    Route::post('/exam/execute', [ExamController::class, 'execute'])->name('exam.execute');
    Route::post('/exam/submit-subnet', [ExamController::class, 'submitSubnet'])->name('exam.submitSubnet');
    Route::post('/exam/submit-config', [ExamController::class, 'submitConfig'])->name('exam.submitConfig');
    Route::post('/exam/finalize', [ExamController::class, 'finalize'])->name('exam.finalize');
    Route::get('/exam/result/{session}', [ExamController::class, 'result'])->name('exam.result');
});

// Memuat semua route untuk autentikasi (login, register, dll)
require __DIR__.'/auth.php';