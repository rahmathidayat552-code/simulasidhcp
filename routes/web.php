<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Models\ExamSession;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Route untuk halaman depan (Welcome Page)
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

// Route untuk Dashboard utama setelah login
// Logika di dalamnya akan mengarahkan Guru dan Siswa ke dasbor masing-masing
Route::get('/dashboard', function () {
    // Cek apakah yang login adalah siswa
    if (Auth::guard('student')->check()) {
        $activeSession = ExamSession::where('student_id', Auth::guard('student')->id())
            ->where('status', 'ongoing')
            ->first();

        if ($activeSession) {
            return redirect()->route('exam.show', $activeSession->id);
        }
        return Inertia::render('Dashboard');
    }

    // Jika yang login adalah guru, arahkan ke dasbor admin
    if (Auth::guard('web')->check()) {
        return redirect()->route('admin.dashboard');
    }

    // Default redirect jika terjadi anomali
    return redirect('/');

})->middleware(['auth:web,student', 'verified'])->name('dashboard');


// Grup route untuk SISWA yang memerlukan autentikasi
Route::middleware('auth:student')->group(function () {
    // Route ini sekarang hanya untuk siswa, jadi kita bisa hapus prefix 'profile'
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

/*--- Route untuk Halaman Admin (untuk GURU) ---*/
Route::middleware(['auth:web'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('students', StudentController::class)->only([
        'index', 'store', 'update', 'destroy'
    ]);
    
    // Placeholder untuk route baru (bisa dikembangkan nanti)
    Route::get('/exam-results', function () { /* ... */ })->name('exam-results.index');
    Route::get('/exam-settings', function () { /* ... */ })->name('exam-settings.index');
});

// Memuat semua route autentikasi (login, register, dll.)
require __DIR__.'/auth.php';