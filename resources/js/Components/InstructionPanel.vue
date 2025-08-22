<script setup>
import { computed } from 'vue';

const props = defineProps({
    currentStep: {
        type: Number,
        required: true,
    },
});

// Daftar instruksi yang sudah disesuaikan dengan alur aplikasi
const instructions = {
    1: "Langkah pertama adalah melakukan instalasi DHCP Server. Jalankan perintah `sudo apt update` lalu `sudo apt install isc-dhcp-server -y`.",
    2: "Tentukan alamat jaringan (network) dan subnet mask yang akan digunakan untuk server DHCP Anda. Masukkan dalam format CIDR (Contoh: 192.168.1.0/24) pada form di samping.",
    3: "Konfigurasikan alamat IP statis untuk server. Pertama, hapus konfigurasi IP lama, lalu tambahkan IP gateway yang sesuai dengan subnet Anda.",
    4: "Sekarang, edit file konfigurasi utama DHCP server (`dhcpd.conf`) untuk mengatur range IP, DNS, dan parameter lainnya.",
    5: "Tentukan pada interface jaringan mana DHCP server akan berjalan. Edit file `/etc/default/isc-dhcp-server` dan atur `INTERFACESv4`.",
    6: "Terakhir, restart service DHCP untuk menerapkan semua konfigurasi, lalu periksa statusnya untuk memastikan service berjalan tanpa error.",
};

const currentInstruction = computed(() => {
    return instructions[props.currentStep] || "Semua langkah telah selesai. Silakan klik tombol Finalisasi.";
});
</script>

<template>
    <div class="bg-gray-800 text-white p-4 rounded-lg shadow-md h-full">
        <h3 class="font-bold text-lg mb-2 border-b border-gray-600 pb-2">
            Instruksi - Langkah {{ currentStep }}
        </h3>
        <p class="text-sm font-mono whitespace-pre-wrap leading-relaxed">
            {{ currentInstruction }}
        </p>
    </div>
</template>