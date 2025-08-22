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
    protected $commandValidator;
    protected $dhcpConfigParser;
    protected $networkCalculator;

    public function __construct(
        CommandValidator $commandValidator,
        DhcpConfigParser $dhcpConfigParser,
        NetworkCalculator $networkCalculator
    ) {
        $this->commandValidator = $commandValidator;
        $this->dhcpConfigParser = $dhcpConfigParser;
        $this->networkCalculator = $networkCalculator;
    }

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
                'command_step' => 0,
            ],
        ]);

        return redirect()->route('exam.show', $examSession->id);
    }

    public function show(ExamSession $session)
    {
        if ($session->student_id !== auth()->id()) {
            abort(403, 'Anda tidak diizinkan mengakses sesi ujian ini.');
        }
        $session->load('commandLogs');
        return \Inertia\Inertia::render('Exam', [
            'examSession' => $session,
        ]);
    }

    public function execute(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'command' => 'required|string',
            'session_id' => 'required|exists:exam_sessions,id',
        ]);

        if ($validator->fails()) {
            // Jika validasi gagal, kembali dengan error.
            // Inertia akan menangani ini dengan benar.
            return back()->withErrors($validator->errors());
        }
        
        $session = ExamSession::findOrFail($request->session_id);
        $sessionData = $session->session_data;
        $currentStep = $sessionData['current_step'] ?? 1;
        $commandStep = $sessionData['command_step'] ?? 0;

        $isValid = $this->commandValidator->isValid($currentStep, $commandStep, $request->command, $sessionData);
        
        if ($isValid && $currentStep === 6 && $commandStep === 0) {
            $isSuccess = $this->_evaluateExamSession($session);
            $sessionData['final_result_precalculated'] = $isSuccess ? 'Active (Running)' : 'Failed';
        }

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

        if ($isValid) {
            $sessionData['command_step']++;
            if ($this->commandValidator->isStepCompleted($currentStep, $sessionData['command_step'])) {
                $sessionData['current_step']++;
                $sessionData['command_step'] = 0;
            }
            $session->session_data = $sessionData;
            $session->save();
        }

        // UBAH BAGIAN INI: Hapus response()->json() dan ganti dengan redirect.
        // Ini memastikan Inertia selalu menerima respon yang valid.
        return redirect()->route('exam.show', $session->id);
    }

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
        $sessionData['current_step'] = 3;
        $sessionData['command_step'] = 0;

        $session->session_data = $sessionData;
        $session->save();

        return redirect()->route('exam.show', $session->id);
    }

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
            return back()->withErrors(['config_content' => 'Aksi tidak valid.']);
        }

        $session->session_data = $sessionData;
        $session->save();

        // Di sini kita juga redirect agar konsisten
        return redirect()->route('exam.show', $session->id);
    }

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

    public function result(ExamSession $session)
    {
        if ($session->student_id !== auth()->id()) {
            abort(403);
        }
        $session->load('student');

        return \Inertia\Inertia::render('Result', [
            'examSession' => $session,
        ]);
    }
    
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