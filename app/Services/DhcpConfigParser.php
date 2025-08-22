<?php

namespace App\Services;

class DhcpConfigParser
{
    /**
     * Mengevaluasi konten file utama dhcpd.conf (Langkah 4).
     *
     * @param string $configContent Teks mentah dari file.
     * @param array $sessionData Data dari sesi ujian (berisi subnet, netmask, dll).
     * @return bool
     */
    public function evaluateDhcpdConfig(string $configContent, array $sessionData): bool
    {
        // Pastikan data yang dibutuhkan dari langkah sebelumnya ada
        if (!isset($sessionData['subnet'], $sessionData['netmask'], $sessionData['gateway'])) {
            return false;
        }

        $cleanedContent = $this->cleanConfig($configContent);

        // Definisikan kriteria kelulusan secara dinamis
        $criteria = [
            'has_authoritative' => str_contains($cleanedContent, 'authoritative;'),
            'has_subnet' => preg_match(
                "/subnet\s+{$sessionData['subnet']}\s+netmask\s+{$sessionData['netmask']}/",
                $cleanedContent
            ),
            'has_routers' => preg_match(
                "/option\s+routers\s+{$sessionData['gateway']};/",
                $cleanedContent
            ),
            // Anda bisa membuat validasi range dan dns lebih spesifik jika perlu
            'has_range' => preg_match("/range\s+[\d\.]+\s+[\d\.]+;/", $cleanedContent),
            'has_dns' => preg_match("/option\s+domain-name-servers\s+[\d\.]+(,\s*[\d\.]+)*;/", $cleanedContent),
        ];

        // Jika salah satu kriteria tidak terpenuhi, maka gagal.
        foreach ($criteria as $check) {
            if (!$check) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mengevaluasi konten file interface /etc/default/isc-dhcp-server (Langkah 5).
     *
     * @param string $configContent Teks mentah dari file.
     * @return bool
     */
    public function evaluateInterfaceConfig(string $configContent): bool
    {
        // Cari baris INTERFACESv4="eth0"
        // Menggunakan regex untuk menangani spasi dan kutip yang berbeda
        return preg_match('/INTERFACESv4\s*=\s*["\']eth0["\']/', $configContent) === 1;
    }

    /**
     * Membersihkan string konfigurasi dari komentar dan baris kosong.
     */
    private function cleanConfig(string $content): string
    {
        $lines = explode("\n", $content);
        $cleanedLines = [];

        foreach ($lines as $line) {
            $line = trim(preg_replace('/#.*$/', '', $line));
            if (!empty($line)) {
                $cleanedLines[] = $line;
            }
        }

        return implode(' ', $cleanedLines);
    }
}