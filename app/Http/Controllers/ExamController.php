<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExamSession;
use App\Models\CommandLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
// Impor service class yang baru dibuat
use App\Services\CommandValidator;
use App\Services\DhcpConfigParser;

class ExamController extends Controller
{
    public function show(ExamSession $session)
{
    // Pastikan siswa hanya bisa mengakses sesi ujian miliknya
    if ($session->student_id !== auth()->id()) {
        abort(403, 'Anda tidak diizinkan mengakses sesi ujian ini.');
    }

    // Ambil semua log perintah yang terkait dengan sesi ini untuk ditampilkan kembali
    $session->load('commandLogs');

    return \Inertia\Inertia::render('Exam', [
        'examSession' => $session,
    ]);
}


    // Tambahkan properti untuk menampung instance service
    protected $commandValidator;
    protected $dhcpConfigParser;

    // Gunakan dependency injection untuk memasukkan service ke controller
    public function __construct(CommandValidator $commandValidator, DhcpConfigParser $dhcpConfigParser)
    {
        $this->commandValidator = $commandValidator;
        $this->dhcpConfigParser = $dhcpConfigParser;
    }

    // ... (Fungsi start() tidak perlu diubah)
    public function start()
    {
        $existingSession = ExamSession::where('student_id', Auth::id())
            ->where('status', 'ongoing')
            ->first();

        if ($existingSession) {
            return redirect()->route('exam.show', $existingSession->id);
        }

        $examSession = ExamSession::create([
            'student_id' => Auth::id(),
            'status' => 'ongoing',
            'start_time' => now(),
            'session_data' => ['current_step' => 1],
        ]);

        public function result(ExamSession $session)
{
    // Pastikan siswa hanya bisa mengakses hasil ujian miliknya
    if ($session->student_id !== auth()->id()) {
        abort(403);
    }

    // Load relasi student untuk menampilkan nama di halaman hasil
    $session->load('student');

    return \Inertia\Inertia::render('Result', [
        'examSession' => $session,
    ]);
}

        return redirect()->route('exam.show', $examSession->id);
    }


    /**
     * Mengeksekusi perintah (sekarang menggunakan CommandValidator).
     */
    public function execute(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'command' => 'required|string',
            'session_id' => 'required|exists:exam_sessions,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }
        
        $session = ExamSession::findOrFail($request->session_id);
        $currentStep = $session->session_data['current_step'] ?? 1;

        // --- PANGGIL SERVICE UNTUK VALIDASI ---
        $isValid = $this->commandValidator->isValid($currentStep, $request->command);
        $output = $isValid
            ? $this->commandValidator->getSuccessOutput($currentStep)
            : "bash: command not found: " . explode(' ', $request->command)[0];

        // Simpan log
        CommandLog::create([
            'exam_session_id' => $session->id,
            'step_number' => $currentStep,
            'command' => $request->command,
            'is_correct' => $isValid,
            'response_output' => $output,
        ]);

        // Jika valid, maju ke langkah berikutnya
        if ($isValid) {
            $sessionData = $session->session_data;
            $sessionData['current_step'] = $currentStep + 1;
            $session->session_data = $sessionData;
            $session->save();
        }

        return response()->json(['output' => $output, 'is_correct' => $isValid]);
    }

    // ... (Fungsi submitConfig() tidak perlu diubah)
    public function submitConfig(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:exam_sessions,id',
            'config_content' => 'required|string',
        ]);

        $session = ExamSession::findOrFail($request->session_id);

        $sessionData = $session->session_data;
        $sessionData['dhcp_config'] = $request->config_content;
        $session->session_data = $sessionData;
        $session->save();

        return response()->json(['message' => 'Konfigurasi berhasil disimpan.']);
    }

    /**
     * Mengevaluasi hasil akhir (sekarang menggunakan DhcpConfigParser).
     */
    public function finalize(Request $request)
    {
        $request->validate(['session_id' => 'required|exists:exam_sessions,id']);
        $session = ExamSession::findOrFail($request->session_id);
        
        $isSuccess = false;
        $configContent = $session->session_data['dhcp_config'] ?? '';

        // --- PANGGIL SERVICE UNTUK EVALUASI ---
        if (!empty($configContent)) {
            $isSuccess = $this->dhcpConfigParser->evaluate($configContent);
        }

        // Update status sesi ujian
        $session->update([
            'status' => 'completed',
            'final_result' => $isSuccess ? 'Active (Running)' : 'Failed',
            'end_time' => now(),
        ]);

        return redirect()->route('exam.result', $session->id);
    }
}