<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\ExamSession;
use App\Models\CommandLog;
use App\Services\CommandValidator;
use App\Services\DhcpConfigParser;
use App\Services\NetworkCalculator;

class ExamController extends Controller
{
    /**
     * Service untuk validasi perintah.
     * @var CommandValidator
     */
    protected $commandValidator;

    /**
     * Service untuk parsing file konfigurasi DHCP.
     * @var DhcpConfigParser
     */
    protected $dhcpConfigParser;

    /**
     * Service untuk kalkulasi jaringan (CIDR).
     * @var NetworkCalculator
     */
    protected $networkCalculator;

    /**
     * Constructor untuk menginjeksi semua service yang dibutuhkan.
     */
    public function __construct(
        CommandValidator $commandValidator,
        DhcpConfigParser $dhcpConfigParser,
        NetworkCalculator $networkCalculator
    ) {
        $this->commandValidator = $commandValidator;
        $this->dhcpConfigParser = $dhcpConfigParser;
        $this->networkCalculator = $networkCalculator;
    }

    /**
     * Memulai sesi ujian baru untuk siswa yang sedang login.
     * Menginisialisasi state awal di session_data.
     */
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
            'session_data' => [
                'current_step' => 1,
                'command_step' => 0, // Index untuk sub-perintah dalam satu langkah
            ],
        ]);

        return redirect()->route('exam.show', $examSession->id);
    }

    /**
     * Menampilkan halaman ujian utama.
     * Mengirim data sesi dan log perintah ke frontend Vue.
     */
    public function show(ExamSession $session)
    {
        // Otorisasi: pastikan siswa hanya bisa mengakses sesi miliknya.
        if ($session->student_id !== auth()->id()) {
            abort(403, 'Anda tidak diizinkan mengakses sesi ujian ini.');
        }

        $session->load('commandLogs'); // Eager load relasi commandLogs

        return \Inertia\Inertia::render('Exam', [
            'examSession' => $session,
        ]);
    }

    /**
     * Mengeksekusi perintah dari terminal simulasi.
     * Ini adalah endpoint utama yang dipanggil oleh frontend.
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
        $sessionData = $session->session_data;
        $currentStep = $sessionData['current_step'] ?? 1;
        $commandStep = $sessionData['command_step'] ?? 0;

        $isValid = $this->commandValidator->isValid($currentStep, $commandStep, $request->command, $sessionData);
        
        // Logika khusus untuk Langkah 6: saat me-restart service
        if ($isValid && $currentStep === 6 && $commandStep === 0) {
            // Lakukan evaluasi akhir dan simpan hasilnya di session_data untuk digunakan nanti
            $isSuccess = $this->_evaluateExamSession($session);
            $sessionData['final_result_precalculated'] = $isSuccess ? 'Active (Running)' : 'Failed';
        }

        // Dapatkan output simulasi (bisa dinamis berdasarkan hasil evaluasi)
        $output = $isValid
            ? $this->commandValidator->getSuccessOutput($currentStep, $commandStep, $sessionData)
            : "bash: command not found: " . explode(' ', $request->command)[0];

        CommandLog::create([
            'exam_session_id' => $session->id,
            'step_number' => $currentStep,
            'command' => $request->command,
            'is_correct' => $isValid,
            'response_output' => $output,
        ]);

        // Jika perintah valid, perbarui state sesi
        if ($isValid) {
            $sessionData['command_step']++;
            if ($this->commandValidator->isStepCompleted($currentStep, $sessionData['command_step'])) {
                $sessionData['current_step']++;
                $sessionData['command_step'] = 0; // Reset untuk langkah berikutnya
            }
            $session->session_data = $sessionData;
            $session->save();
        }

        return response()->json(['output' => $output, 'is_correct' => $isValid]);
    }

    /**
     * Menerima dan memvalidasi input subnet dari form (Langkah 2).
     */
    public function submitSubnet(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:exam_sessions,id',
            'subnet_cidr' => 'required|string',
        ]);

        $session = ExamSession::findOrFail($request->session_id);

        if (($session->session_data['current_step'] ?? 1) != 2) {
            return back()->withErrors(['subnet_cidr' => 'Aksi tidak diizinkan pada langkah ini.']);
        }

        $networkInfo = $this->networkCalculator->parseCidr($request->subnet_cidr);

        if (!$networkInfo) {
            return back()->withErrors(['subnet_cidr' => 'Format CIDR tidak valid. Contoh: 192.168.1.0/24']);
        }

        $sessionData = $session->session_data;
        $sessionData['subnet'] = $networkInfo['subnet'];
        $sessionData['netmask'] = $networkInfo['netmask'];
        $sessionData['gateway'] = $networkInfo['gateway'];
        $sessionData['current_step'] = 3; // Lanjut ke langkah 3
        $sessionData['command_step'] = 0;

        $session->session_data = $sessionData;
        $session->save();

        return redirect()->route('exam.show', $session->id);
    }

    /**
     * Menyimpan konten dari editor simulasi (Langkah 4 & 5).
     */
    public function submitConfig(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:exam_sessions,id',
            'config_content' => 'nullable|string',
        ]);

        $session = ExamSession::findOrFail($request->session_id);
        $sessionData = $session->session_data;
        $currentStep = $sessionData['current_step'] ?? 1;

        if ($currentStep === 4) {
            $sessionData['dhcpd_config_content'] = $request->config_content;
        } elseif ($currentStep === 5) {
            $sessionData['interface_config_content'] = $request->config_content;
        } else {
            return response()->json(['message' => 'Aksi tidak valid untuk langkah ini.'], 400);
        }

        $session->session_data = $sessionData;
        $session->save();

        return response()->json(['message' => 'Konfigurasi berhasil disimpan.']);
    }

    /**
     * Menyelesaikan ujian dan menyimpan hasil akhir.
     * Dipanggil saat siswa menekan tombol "Finalisasi".
     */
    public function finalize(Request $request)
    {
        $request->validate(['session_id' => 'required|exists:exam_sessions,id']);
        $session = ExamSession::findOrFail($request->session_id);
        
        $isSuccess = $this->_evaluateExamSession($session);

        $session->update([
            'status' => 'completed',
            'final_result' => $isSuccess ? 'Active (Running)' : 'Failed',
            'end_time' => now(),
        ]);

        return redirect()->route('exam.result', $session->id);
    }

    /**
     * Menampilkan halaman hasil akhir ujian.
     */
    public function result(ExamSession $session)
    {
        if ($session->student_id !== auth()->id()) {
            abort(403);
        }
        $session->load('student'); // Load nama siswa untuk ditampilkan

        return \Inertia\Inertia::render('Result', [
            'examSession' => $session,
        ]);
    }
    
    /**
     * Metode privat terpusat untuk mengevaluasi semua konfigurasi sesi ujian.
     *
     * @param ExamSession $session
     * @return bool True jika semua konfigurasi valid, false jika ada yang salah.
     */
    private function _evaluateExamSession(ExamSession $session): bool
    {
        $sessionData = $session->session_data;

        $dhcpdConfig = $sessionData['dhcpd_config_content'] ?? '';
        $interfaceConfig = $sessionData['interface_config_content'] ?? '';

        $isDhcpdValid = $this->dhcpConfigParser->evaluateDhcpdConfig($dhcpdConfig, $sessionData);
        $isInterfaceValid = $this->dhcpConfigParser->evaluateInterfaceConfig($interfaceConfig);

        return $isDhcpdValid && $isInterfaceValid;
    }
}