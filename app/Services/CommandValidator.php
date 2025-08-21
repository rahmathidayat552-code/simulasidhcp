<?php

namespace App\Services;

class CommandValidator
{
    /**
     * Daftar perintah yang diharapkan untuk setiap langkah.
     * Kunci array adalah nomor langkah, dan nilainya adalah perintah yang valid.
     * Anda bisa menggunakan array jika ada beberapa alternatif perintah yang benar.
     * @var array
     */
    private const EXPECTED_COMMANDS = [
        1 => 'sudo apt update',
        2 => 'sudo apt install isc-dhcp-server',
        3 => 'sudo nano /etc/dhcp/dhcpd.conf',
        4 => 'sudo nano /etc/default/isc-dhcp-server',
        5 => 'sudo systemctl restart isc-dhcp-server',
        6 => 'sudo systemctl status isc-dhcp-server',
    ];

    /**
     * Memvalidasi apakah sebuah perintah valid untuk langkah tertentu.
     *
     * @param int $step Nomor langkah saat ini.
     * @param string $command Perintah yang dimasukkan oleh siswa.
     * @return bool
     */
    public function isValid(int $step, string $command): bool
    {
        // Menghapus spasi berlebih untuk konsistensi
        $trimmedCommand = trim($command);

        if (!isset(self::EXPECTED_COMMANDS[$step])) {
            // Jika tidak ada aturan untuk langkah ini, anggap salah.
            return false;
        }

        $expected = self::EXPECTED_COMMANDS[$step];

        // Jika aturan adalah string, cocokkan secara langsung
        if (is_string($expected)) {
            return $trimmedCommand === $expected;
        }

        // Jika aturan adalah array (untuk beberapa kemungkinan), cek salah satunya
        if (is_array($expected)) {
            return in_array($trimmedCommand, $expected);
        }

        return false;
    }

    /**
     * Memberikan output simulasi untuk perintah yang valid.
     *
     * @param int $step
     * @return string
     */
    public function getSuccessOutput(int $step): string
    {
        $outputs = [
            1 => "Reading package lists... Done\nBuilding dependency tree... Done\nAll packages are up to date.",
            2 => "Reading package lists... Done\nBuilding dependency tree... Done\nisc-dhcp-server is already the newest version.",
            3 => "Opening nano editor for dhcpd.conf...", // Frontend akan menangani ini
            4 => "Opening nano editor for isc-dhcp-server default config...", // Frontend akan menangani ini
            5 => "Restarting DHCP server...",
            6 => "● isc-dhcp-server.service - ISC DHCP IPv4 server\n   Loaded: loaded (/lib/systemd/system/isc-dhcp-server.service; enabled; vendor preset: enabled)\n   Active: active (running) since Thu 2023-10-26 10:00:00 UTC; 5s ago",
        ];

        return $outputs[$step] ?? 'Command executed successfully.';
    }
}