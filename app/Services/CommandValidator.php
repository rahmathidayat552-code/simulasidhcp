<?php

namespace App\Services;

class CommandValidator
{
    /**
     * Daftar perintah yang diharapkan untuk setiap langkah ujian.
     */
    private const EXPECTED_COMMANDS = [
        1 => [
            'sudo apt update',
            'sudo apt install isc-dhcp-server -y',
        ],
        2 => [],
        3 => [
            'sudo ip addr flush dev eth0',
            'sudo ip addr add {{gateway}}/24 dev eth0',
        ],
        4 => ['sudo nano /etc/dhcp/dhcpd.conf'],
        5 => ['sudo nano /etc/default/isc-dhcp-server'],
        6 => [
            'sudo systemctl restart isc-dhcp-server',
            'sudo systemctl status isc-dhcp-server'
        ],
    ];

    /**
     * Memvalidasi apakah sebuah perintah valid.
     */
    public function isValid(int $step, int $commandIndex, string $command, array $sessionData = []): bool
    {
        $trimmedCommand = trim($command);

        if (!isset(self::EXPECTED_COMMANDS[$step][$commandIndex])) {
            return false;
        }

        $expected = self::EXPECTED_COMMANDS[$step][$commandIndex];

        if (str_contains($expected, '{{gateway}}')) {
            $gateway = $sessionData['gateway'] ?? null;
            if (empty($gateway)) {
                return false;
            }
            $expected = str_replace('{{gateway}}', $gateway, $expected);
        }

        return $trimmedCommand === $expected;
    }

    /**
     * Memberikan output simulasi untuk perintah yang valid.
     */
    public function getSuccessOutput(int $step, int $commandIndex, array $sessionData = []): string
    {
        if ($step === 6 && $commandIndex === 1) { // Perintah 'status'
            $result = $sessionData['final_result_precalculated'] ?? 'Failed';
            
            if ($result === 'Active (Running)') {
                return "● isc-dhcp-server.service - ISC DHCP IPv4 server\n   Active: active (running) since " . date('Y-m-d H:i:s') . " UTC";
            } else {
                $errors = $sessionData['evaluation_errors'] ?? ['Konfigurasi tidak diketahui.'];
                $errorString = implode("\n", array_map(fn($err) => " - {$err}", $errors));
                return "● isc-dhcp-server.service - ISC DHCP IPv4 server\n   Active: failed (Result: exit-code) since " . date('Y-m-d H:i:s') . " UTC\n\nDetail Kesalahan:\n{$errorString}";
            }
        }
        
        $outputs = [
            1 => [
                "Reading package lists... Done.",
                "isc-dhcp-server is already the newest version.",
            ],
            3 => [
                "Successfully flushed interface eth0.",
                "Static IP address configured successfully on eth0.",
            ],
            4 => ["Opening nano editor for /etc/dhcp/dhcpd.conf..."],
            5 => ["Opening nano editor for /etc/default/isc-dhcp-server..."],
            6 => [
                "Restarting ISC DHCP Server...",
            ],
        ];

        return $outputs[$step][$commandIndex] ?? 'Command executed successfully.';
    }

    /**
     * Memeriksa apakah semua sub-perintah dalam satu langkah sudah selesai.
     */
    public function isStepCompleted(int $step, int $nextCommandIndex): bool
    {
        if (!isset(self::EXPECTED_COMMANDS[$step])) {
            return true;
        }
        
        return $nextCommandIndex >= count(self::EXPECTED_COMMANDS[$step]);
    }
    public function isNanoCommand(string $command): bool
    {
        return str_starts_with(trim($command), 'sudo nano');
    }
}