<?php

namespace App\Services;

class CommandValidator
{
    /**
     * Daftar perintah yang diharapkan untuk setiap langkah ujian.
     * Setiap langkah adalah array yang berisi satu atau lebih sub-perintah.
     * {{gateway}} adalah placeholder yang akan diganti dengan data dari sesi ujian.
     * @var array
     */
    private const EXPECTED_COMMANDS = [
        // Langkah 1: Instalasi
        1 => [
            'sudo apt update',
            'sudo apt install isc-dhcp-server -y',
        ],
        // Langkah 2: Ditangani oleh form input, bukan terminal. Array sengaja dikosongkan.
        2 => [],
        // Langkah 3: Konfigurasi IP Statis
        3 => [
            'sudo ip addr flush dev eth0',
            'sudo ip addr add {{gateway}}/24 dev eth0', // Perintah dinamis
        ],
        // Langkah 4: Edit Konfigurasi Utama DHCP
        4 => ['sudo nano /etc/dhcp/dhcpd.conf'],
        // Langkah 5: Edit Konfigurasi Interface
        5 => ['sudo nano /etc/default/isc-dhcp-server'],
        // Langkah 6: Restart & Cek Status
        6 => [
            'sudo systemctl restart isc-dhcp-server',
            'sudo systemctl status isc-dhcp-server'
        ],
    ];

    /**
     * Memvalidasi apakah sebuah perintah valid untuk langkah dan sub-langkah tertentu.
     * Metode ini mendukung placeholder dinamis dari data sesi.
     *
     * @param int $step Nomor langkah utama saat ini.
     * @param int $commandIndex Index perintah yang diharapkan dalam array langkah (sub-langkah).
     * @param string $command Perintah yang dimasukkan oleh siswa.
     * @param array $sessionData Data sesi yang mungkin berisi nilai untuk placeholder.
     * @return bool True jika valid, false jika tidak.
     */
    public function isValid(int $step, int $commandIndex, string $command, array $sessionData = []): bool
    {
        $trimmedCommand = trim($command);

        // Cek apakah ada aturan validasi untuk langkah dan sub-langkah ini.
        if (!isset(self::EXPECTED_COMMANDS[$step][$commandIndex])) {
            return false;
        }

        $expected = self::EXPECTED_COMMANDS[$step][$commandIndex];

        // Ganti placeholder {{gateway}} dengan nilai sebenarnya dari sessionData.
        if (str_contains($expected, '{{gateway}}')) {
            $gateway = $sessionData['gateway'] ?? null;
            // Jika placeholder ada tapi datanya tidak ditemukan di sesi, validasi gagal.
            if (empty($gateway)) {
                return false;
            }
            // Ganti placeholder dengan data yang benar.
            $expected = str_replace('{{gateway}}', $gateway, $expected);
        }

        // Bandingkan perintah yang sudah bersih dengan yang diharapkan.
        return $trimmedCommand === $expected;
    }

    /**
     * Memberikan output simulasi untuk perintah yang valid.
     *
     * @param int $step Nomor langkah utama.
     * @param int $commandIndex Index sub-langkah.
     * @param array $sessionData Data sesi untuk menghasilkan output dinamis.
     * @return string Output terminal yang disimulasikan.
     */
    public function getSuccessOutput(int $step, int $commandIndex, array $sessionData = []): string
    {
        // Logika khusus untuk perintah 'status' di Langkah 6
        if ($step === 6 && $commandIndex === 1) {
            $precalculatedResult = $sessionData['final_result_precalculated'] ?? 'Failed';
            
            if ($precalculatedResult === 'Active (Running)') {
                return "● isc-dhcp-server.service - ISC DHCP IPv4 server\n   Loaded: loaded (/lib/systemd/system/isc-dhcp-server.service; enabled)\n   Active: active (running) since " . date('Y-m-d H:i:s') . " UTC\n   Docs: man:dhcpd(8)\n Main PID: 1234 (dhcpd)\n   Status: \"Dispatching packets...\"\n    Tasks: 1 (limit: 4662)\n   Memory: 1.2M\n   CGroup: /system.slice/isc-dhcp-server.service\n           └─1234 /usr/sbin/dhcpd -f -cf /etc/dhcp/dhcpd.conf -user dhcpd -group dhcpd --no-pid eth0";
            } else {
                return "● isc-dhcp-server.service - ISC DHCP IPv4 server\n   Loaded: loaded (/lib/systemd/system/isc-dhcp-server.service; enabled)\n   Active: failed (Result: exit-code) since " . date('Y-m-d H:i:s') . " UTC\n  Process: 1234 ExecStart=/bin/sh -ec /usr/lib/isc-dhcp-server/dhcpd-startup (code=exited, status=1/FAILURE)\n\n-- LSB: isc-dhcp-server failed to start.";
            }
        }
        
        // Logika output statis untuk perintah lainnya
        $outputs = [
            1 => [
                "Reading package lists... Done.\nBuilding dependency tree... Done.\nAll packages are up to date.",
                "Reading package lists... Done.\nBuilding dependency tree... Done.\nisc-dhcp-server is already the newest version.",
            ],
            2 => [],
            3 => [
                "Successfully flushed interface eth0.",
                "Static IP address configured successfully on eth0.",
            ],
            4 => ["Opening nano editor for /etc/dhcp/dhcpd.conf..."],
            5 => ["Opening nano editor for /etc/default/isc-dhcp-server..."],
            6 => [
                "Restarting ISC DHCP Server...", // Output untuk perintah 'restart'
                // Output untuk 'status' ditangani di atas
            ],
        ];

        return $outputs[$step][$commandIndex] ?? 'Command executed successfully.';
    }

    /**
     * Memeriksa apakah semua sub-perintah dalam satu langkah sudah selesai dieksekusi.
     *
     * @param int $step Nomor langkah utama yang sedang diperiksa.
     * @param int $nextCommandIndex Index dari sub-perintah berikutnya yang akan dieksekusi.
     * @return bool True jika semua sub-perintah telah selesai.
     */
    public function isStepCompleted(int $step, int $nextCommandIndex): bool
    {
        // Jika tidak ada definisi perintah untuk langkah ini, anggap sudah selesai.
        if (!isset(self::EXPECTED_COMMANDS[$step])) {
            return true;
        }
        
        // Selesai jika index berikutnya sama atau lebih besar dari jumlah perintah yang ada.
        return $nextCommandIndex >= count(self::EXPECTED_COMMANDS[$step]);
    }
}