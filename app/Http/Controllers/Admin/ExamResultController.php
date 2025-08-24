<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Services\GradeCalculator; // <-- 1. Import service baru
use Inertia\Inertia;

class ExamResultController extends Controller
{
    /**
     * Menampilkan halaman daftar hasil ujian.
     */
    public function index(GradeCalculator $gradeCalculator) // <-- 2. Injeksi service
    {
        $examResults = ExamSession::with('student')
            ->where('status', 'completed')
            ->latest()
            ->paginate(10);

        // 3. Tambahkan properti 'grade' ke setiap hasil ujian
        $examResults->getCollection()->transform(function ($session) use ($gradeCalculator) {
            $session->grade = $gradeCalculator->calculate($session);
            return $session;
        });

        return Inertia::render('Admin/ExamResults/Index', [
            'examResults' => $examResults,
        ]);
    }
}