<?php

namespace App\Services;

use App\Models\ExamSession;
use Carbon\Carbon;

class GradeCalculator
{
    /**
     * Menghitung grade berdasarkan hasil sesi ujian.
     *
     * @param ExamSession $session
     * @return string Grade (A+, A, B, C, D, E)
     */
    public function calculate(ExamSession $session): string
    {
        if ($session->final_result !== 'Active (Running)' && $session->final_result !== 'Failed') {
            return 'E';
        }

        // --- Logika untuk Grade A+, A, B (Jika Lulus) ---
        if ($session->final_result === 'Active (Running)') {
            if (!$session->start_time || !$session->end_time) {
                return 'N/A'; // Tidak bisa dihitung jika data tidak lengkap
            }

            $startTime = Carbon::parse($session->start_time);
            $endTime = Carbon::parse($session->end_time);
            
            // --- PERBAIKAN LOGIKA UTAMA DI SINI ---
            // Gunakan selisih waktu dalam menit absolut, dibulatkan ke bawah.
            $durationInMinutes = $endTime->diffInMinutes($startTime);

            if ($durationInMinutes < 10) {
                return 'A+';
            } elseif ($durationInMinutes <= 20) {
                return 'A';
            } else {
                return 'B';
            }
        }

        // --- Logika untuk Grade C, D, E (Jika Gagal) ---
        if ($session->final_result === 'Failed') {
            $sessionData = $session->session_data;
            $lastStep = $sessionData['current_step'] ?? 1;

            if ($lastStep >= 6) {
                return 'C';
            } elseif ($lastStep >= 4) {
                return 'D';
            } else {
                return 'E';
            }
        }

        return 'N/A';
    }
}