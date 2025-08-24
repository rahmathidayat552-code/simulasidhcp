<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use Inertia\Inertia;

class ExamResultController extends Controller
{
    /**
     * Menampilkan halaman daftar hasil ujian.
     */
    public function index()
    {
        $examResults = ExamSession::with('student') // Ambil relasi student
            ->where('status', 'completed')    // Hanya ambil yang sudah selesai
            ->latest()                        // Urutkan dari yang terbaru
            ->paginate(10);                   // Paginasi per 10 data

        return Inertia::render('Admin/ExamResults/Index', [
            'examResults' => $examResults,
        ]);
    }
}