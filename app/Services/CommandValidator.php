<?php

namespace App\Services;

class CommandValidator
{
    /**
     * Daftar perintah yang diharapkan untuk setiap langkah ujian.
     */
    private const EXPECTED_COMMANDS = [
        // Langkah 1: Instalasi
        1 => [
            'sudo apt update',
            'sudo apt install isc-dhcp-server -y',
        ],
        // Langkah 2: Ditangani oleh form, bukan terminal.
        2 => [],
        // Langkah 3: Konfigurasi IP Statis
        3 => [
            'sudo ip addr flush dev eth0',
            'sudo ip addr add {{gateway}}/24 dev eth0',
        ],
        // Langkah 4: Edit Konfigurasi Utama DHCP
        4 => ['sudo nano /etc/dhcp/dhcpd.conf'],
        // Langkah 5: Edit Konfigurasi Interface
        5 => ['sudo nano /etc/default/isc-dhcp-server'], // <-- Perintah ini yang penting
        // Langkah 6: Restart & Cek Status
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
        if ($step === 6 && $commandIndex === 1) {
            $precalculatedResult = $sessionData['final_result_precalculated'] ?? 'Failed';
            
            if ($precalculatedResult === 'Active (Running)') {
                return "● isc-dhcp-server.service - ISC DHCP IPv4 server\n   Active: active (running) since " . date('Y-m-d H:i:s') . " UTC";
            } else {
                return "● isc-dhcp-server.service - ISC DHCP IPv4 server\n   Active: failed (Result: exit-code) since " . date('Y-m-d H:i:s') . " UTC";
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
            5 => ["Opening nano editor for /etc/default/isc-dhcp-server..."], // <-- Output untuk perintah ini
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
}