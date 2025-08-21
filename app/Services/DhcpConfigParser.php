<?php

namespace App\Services;

class DhcpConfigParser
{
    /**
     * Mengevaluasi konten file konfigurasi DHCP.
     *
     * @param string $configContent Teks mentah dari file dhcpd.conf.
     * @return bool True jika konfigurasi valid, false jika tidak.
     */
    public function evaluate(string $configContent): bool
    {
        // Normalisasi konten: hapus komentar dan baris kosong untuk mempermudah parsing
        $cleanedContent = $this->cleanConfig($configContent);

        // Definisikan kriteria kelulusan
        $criteria = [
            'has_authoritative' => str_contains($cleanedContent, 'authoritative;'),
            'has_subnet' => preg_match('/subnet\s+192\.168\.1\.0\s+netmask\s+255\.255\.255\.0/', $cleanedContent),
            'has_range' => preg_match('/range\s+192\.168\.1\.10\s+192\.168\.1\.20;/', $cleanedContent),
            'has_dns' => preg_match('/option\s+domain-name-servers\s+8\.8\.8\.8,\s*8\.8\.4\.4;/', $cleanedContent),
            'has_lease_time' => preg_match('/default-lease-time\s+600;/', $cleanedContent),
            'has_max_lease_time' => preg_match('/max-lease-time\s+7200;/', $cleanedContent),
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
     * Membersihkan string konfigurasi dari komentar dan baris kosong.
     *
     * @param string $content
     * @return string
     */
    private function cleanConfig(string $content): string
    {
        $lines = explode("\n", $content);
        $cleanedLines = [];

        foreach ($lines as $line) {
            // Hapus komentar yang dimulai dengan #
            $line = preg_replace('/#.*$/', '', $line);
            // Hapus spasi di awal dan akhir
            $line = trim($line);

            if (!empty($line)) {
                $cleanedLines[] = $line;
            }
        }

        return implode(' ', $cleanedLines);
    }
}