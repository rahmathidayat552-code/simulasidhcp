<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\Admin\StudentController;
use App\Models\ExamSession;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::get('/dashboard', function () {
    $activeSession = ExamSession::where('student_id', Auth::id())
        ->where('status', 'ongoing')
        ->first();

    if ($activeSession) {
        return redirect()->route('exam.show', $activeSession->id);
    }

    return Inertia::render('Dashboard');
})->middleware(['auth:student', 'verified'])->name('dashboard');

// Grup route yang memerlukan autentikasi
Route::middleware('auth:student')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*--- Route untuk Simulasi Ujian (untuk Siswa) ---*/
    Route::post('/exam/start', [ExamController::class, 'start'])->name('exam.start');
    Route::get('/exam/{session}', [ExamController::class, 'show'])->name('exam.show');
    Route::post('/exam/execute', [ExamController::class, 'execute'])->name('exam.execute');
    Route::post('/exam/submit-subnet', [ExamController::class, 'submitSubnet'])->name('exam.submitSubnet');
    Route::post('/exam/submit-config', [ExamController::class, 'submitConfig'])->name('exam.submitConfig');
    Route::post('/exam/finalize', [ExamController::class, 'finalize'])->name('exam.finalize');
    Route::get('/exam/result/{session}', [ExamController::class, 'result'])->name('exam.result');
});

/*--- Route untuk Halaman Admin (untuk Guru) ---*/
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
});

require __DIR__.'/auth.php';