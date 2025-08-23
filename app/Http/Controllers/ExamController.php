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
        $existingSession = ExamSession::where('student_id', Auth::guard('student')->id())
            ->where('status', 'ongoing')
            ->first();

        if ($existingSession) {
            return redirect()->route('exam.show', $existingSession->id);
        }

        $examSession = ExamSession::create([
            'student_id' => Auth::guard('student')->id(),
            'status' => 'ongoing',
            'start_time' => now(),
            'duration' => 25, // Durasi default 25 menit
            'session_data' => [
                'current_step' => 1,
                'command_step' => 0,
            ],
        ]);

        return redirect()->route('exam.show', $examSession->id);
    }

    public function show(ExamSession $session)
    {
        if ($session->student_id !== Auth::guard('student')->id()) {
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
            return back()->withErrors($validator->errors());
        }
        
        $session = ExamSession::findOrFail($request->session_id);
        $sessionData = $session->session_data;
        $currentStep = $sessionData['current_step'] ?? 1;
        $commandStep = $sessionData['command_step'] ?? 0;
    
        // Logika untuk Mode Latihan (jika siswa sudah mencapai langkah akhir)
        if ($currentStep >= 7) {
            $executedCmd = trim($request->command);
            $output = "bash: command not found: " . explode(' ', $executedCmd)[0];
            $isValid = false;
    
            // Loop melalui semua kemungkinan perintah yang bisa diulang
            for ($practiceStep = 2; $practiceStep <= 6; $practiceStep++) {
                foreach (CommandValidator::EXPECTED_COMMANDS[$practiceStep] as $idx => $cmdTemplate) {
                    $expectedCmd = $cmdTemplate;
                    if (str_contains($expectedCmd, '{{gateway}}')) {
                        $gateway = $sessionData['gateway'] ?? '';
                        if(empty($gateway)) continue;
                        $expectedCmd = str_replace('{{gateway}}', $gateway, $expectedCmd);
                    }
    
                    if ($executedCmd === $expectedCmd) {
                        $isValid = true;
                        // Jika perintah restart, jalankan evaluasi ulang
                        if ($practiceStep === 6 && $idx === 0) {
                             $evaluation = $this->_evaluateExamSession($session);
                             $sessionData['final_result_precalculated'] = $evaluation['isSuccess'] ? 'Active (Running)' : 'Failed';
                             $sessionData['evaluation_errors'] = $evaluation['errors'];
                             $session->session_data = $sessionData;
                             $session->save();
                        }
                        $output = $this->commandValidator->getSuccessOutput($practiceStep, $idx, $sessionData);
                        break 2; // Keluar dari kedua loop
                    }
                }
            }
    
            CommandLog::create([
                'exam_session_id' => $session->id,
                'step_number' => $currentStep,
                'command' => $request->command,
                'is_correct' => $isValid,
                'response_output' => $output,
            ]);
    
            return redirect()->route('exam.show', $session->id);
        }
    
        // Alur Ujian Normal (Langkah 1-6)
        $isValid = $this->commandValidator->isValid($currentStep, $commandStep, $request->command, $sessionData);
        
        if ($isValid && $currentStep === 6 && $commandStep === 0) {
            $evaluation = $this->_evaluateExamSession($session);
            $sessionData['final_result_precalculated'] = $evaluation['isSuccess'] ? 'Active (Running)' : 'Failed';
            $sessionData['evaluation_errors'] = $evaluation['errors'];
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
            if (!$this->commandValidator->isNanoCommand($request->command)) {
                $sessionData['command_step']++;
                if ($this->commandValidator->isStepCompleted($currentStep, $sessionData['command_step'])) {
                    $sessionData['current_step']++;
                    $sessionData['command_step'] = 0;
                }
                $session->session_data = $sessionData;
                $session->save();
            }
        }
    
        return redirect()->route('exam.show', $session->id);
    }

    public function submitSubnet(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:exam_sessions,id',
            'subnet_cidr' => 'required|string',
        ]);
        $session = ExamSession::findOrFail($request->session_id);
        $currentStep = $session->session_data['current_step'] ?? 1;

        // Izinkan perubahan jika di langkah 2 atau di mode latihan
        if ($currentStep > 2 && $currentStep < 7) {
            return back()->withErrors(['subnet_cidr' => 'Tidak dapat mengubah subnet di tengah ujian.']);
        }

        $networkInfo = $this->networkCalculator->parseCidr($request->subnet_cidr);
        if (!$networkInfo) {
            return back()->withErrors(['subnet_cidr' => 'Format CIDR tidak valid. Contoh: 192.168.1.0/24']);
        }
        
        $sessionData = $session->session_data;
        $sessionData['subnet'] = $networkInfo['subnet'];
        $sessionData['netmask'] = $networkInfo['netmask'];
        $sessionData['gateway'] = $networkInfo['gateway'];
        
        // Reset langkah ke 3 agar siswa mengkonfigurasi ulang IP statis
        $sessionData['current_step'] = 3;
        $sessionData['command_step'] = 0;
        
        // Hapus konfigurasi lama yang bergantung pada subnet
        unset($sessionData['dhcpd_config_content']);
        unset($sessionData['interface_config_content']);

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

        $isPracticeMode = $currentStep >= 7;
        
        // Tentukan file mana yang diedit berdasarkan perintah terakhir
        $lastCommand = CommandLog::where('exam_session_id', $session->id)->latest()->first()->command ?? '';

        if (str_contains($lastCommand, 'dhcpd.conf')) {
            $sessionData['dhcpd_config_content'] = $request->config_content;
             if (!$isPracticeMode) $sessionData['current_step'] = 5; // Maju ke langkah 5
        } elseif (str_contains($lastCommand, 'isc-dhcp-server')) {
            $sessionData['interface_config_content'] = $request->config_content;
             if (!$isPracticeMode) $sessionData['current_step'] = 6; // Maju ke langkah 6
        } else {
            return back()->withErrors(['config_content' => 'Aksi tidak valid.']);
        }

        if (!$isPracticeMode) $sessionData['command_step'] = 0;
        
        $session->session_data = $sessionData;
        $session->save();
        return redirect()->route('exam.show', $session->id);
    }

    public function finalize(Request $request)
    {
        $request->validate(['session_id' => 'required|exists:exam_sessions,id']);
        $session = ExamSession::findOrFail($request->session_id);
        
        $evaluation = $this->_evaluateExamSession($session);
        $session->update([
            'status' => 'completed',
            'final_result' => $evaluation['isSuccess'] ? 'Active (Running)' : 'Failed',
            'end_time' => now(),
        ]);
        return redirect()->route('exam.result', $session->id);
    }

    public function result(ExamSession $session)
    {
        if ($session->student_id !== Auth::guard('student')->id()) {
            abort(403);
        }
        $session->load('student');
        return \Inertia\Inertia::render('Result', [
            'examSession' => $session,
        ]);
    }
    
    private function _evaluateExamSession(ExamSession $session): array
    {
        $sessionData = $session->session_data;
        $dhcpdConfig = $sessionData['dhcpd_config_content'] ?? '';
        $interfaceConfig = $sessionData['interface_config_content'] ?? '';
        $dhcpdResult = $this->dhcpConfigParser->evaluateDhcpdConfig($dhcpdConfig, $sessionData);
        $interfaceResult = $this->dhcpConfigParser->evaluateInterfaceConfig($interfaceConfig);
        $allErrors = array_merge($dhcpdResult['errors'], $interfaceResult['errors']);
        $isSuccess = $dhcpdResult['isValid'] && $interfaceResult['isValid'];
        return [
            'isSuccess' => $isSuccess,
            'errors' => $allErrors,
        ];
    }
}