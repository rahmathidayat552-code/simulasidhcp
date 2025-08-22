<?php

namespace App\Services;

class NetworkCalculator
{
    /**
     * Mem-validasi dan mengurai notasi CIDR (misal: 192.168.1.0/24).
     *
     * @param string $cidr
     * @return array|null
     */
    public function parseCidr(string $cidr): ?array
    {
        // 1. Validasi format umum menggunakan regex
        if (!preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/(\d{1,2})$/', $cidr, $matches)) {
            return null;
        }

        $ip = $matches[1];
        $maskBits = (int) $matches[2];

        // 2. Validasi alamat IP dan jangkauan subnet mask (misal: /8 sampai /30)
        if (!filter_var($ip, FILTER_VALIDATE_IP) || $maskBits < 8 || $maskBits > 30) {
            return null;
        }

        // 3. Kalkulasi alamat network, subnet mask, dan gateway (IP pertama yang bisa dipakai)
        $ipLong = ip2long($ip);
        $maskLong = -1 << (32 - $maskBits);
        $networkLong = $ipLong & $maskLong;
        
        $subnetAddress = long2ip($networkLong);
        $netmask = long2ip($maskLong);
        $gateway = long2ip($networkLong + 1); // Gateway adalah network address + 1

        return [
            'subnet' => $subnetAddress,
            'netmask' => $netmask,
            'gateway' => $gateway,
        ];
    }
}