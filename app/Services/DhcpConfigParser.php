<?php

namespace App\Services;

class DhcpConfigParser
{
    /**
     * Mengevaluasi file dhcpd.conf dan memberikan skor/nilai.
     *
     * @param string $configContent
     * @param array $sessionData
     * @return array ['isValid' => bool, 'errors' => array]
     */
    public function evaluateDhcpdConfig(string $configContent, array $sessionData): array
    {
        $errors = [];
        if (!isset($sessionData['subnet'], $sessionData['netmask'], $sessionData['gateway'])) {
            $errors[] = 'Data subnet dari Langkah 2 tidak ditemukan.';
            return ['isValid' => false, 'errors' => $errors];
        }

        $cleanedContent = $this->cleanConfig($configContent);

        // --- PERUBAHAN DI SINI ---
        // Kriteria pengecekan untuk 'authoritative' sudah dihapus.
        if (!preg_match("/subnet\s+{$sessionData['subnet']}\s+netmask\s+{$sessionData['netmask']}/", $cleanedContent)) {
            $errors[] = "dhcpd.conf: Deklarasi subnet/netmask tidak sesuai (seharusnya untuk {$sessionData['subnet']}).";
        }
        if (!preg_match("/option\s+routers\s+{$sessionData['gateway']};/", $cleanedContent)) {
            $errors[] = "dhcpd.conf: Opsi router (gateway) tidak sesuai (seharusnya {$sessionData['gateway']}).";
        }
        if (!preg_match("/range\s+[\d\.]+\s+[\d\.]+;/", $cleanedContent)) {
            $errors[] = 'dhcpd.conf: "range" untuk alamat IP tidak ditemukan atau salah format.';
        }
        if (!preg_match("/option\s+domain-name-servers/", $cleanedContent)) {
            $errors[] = 'dhcpd.conf: "option domain-name-servers" tidak diatur.';
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Mengevaluasi file interface dan memberikan skor/nilai.
     *
     * @param string $configContent
     * @return array ['isValid' => bool, 'errors' => array]
     */
    public function evaluateInterfaceConfig(string $configContent): array
    {
        $errors = [];
        if (preg_match('/INTERFACESv4\s*=\s*["\']eth0["\']/', $configContent) !== 1) {
            $errors[] = 'interfaces: INTERFACESv4 tidak diatur ke "eth0".';
        }
        
        return [
            'isValid' => empty($errors),
            'errors' => $errors,
        ];
    }
    
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