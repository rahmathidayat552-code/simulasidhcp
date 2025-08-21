<script setup>
import { computed } from 'vue';

const props = defineProps({
    currentStep: {
        type: Number,
        required: true,
    },
});

// Daftar semua instruksi untuk setiap langkah
const instructions = [
    "Langkah ini tidak valid.",
    "Lakukan update package list pada server. Perintah: sudo apt update",
    "Install paket isc-dhcp-server. Perintah: sudo apt install isc-dhcp-server",
    "Buka file konfigurasi utama DHCP server. Perintah: sudo nano /etc/dhcp/dhcpd.conf",
    "Buka file untuk menentukan interface jaringan. Perintah: sudo nano /etc/default/isc-dhcp-server",
    "Restart service DHCP agar konfigurasi baru diterapkan. Perintah: sudo systemctl restart isc-dhcp-server",
    "Periksa status service untuk memastikan berjalan tanpa error. Perintah: sudo systemctl status isc-dhcp-server",
];

const currentInstruction = computed(() => {
    return instructions[props.currentStep] || "Semua langkah telah selesai. Silakan klik tombol Finalisasi.";
});
</script>

<template>
    <div class="bg-gray-800 text-white p-4 rounded-lg shadow-md h-full">
        <h3 class="font-bold text-lg mb-2 border-b border-gray-600 pb-2">
            Instruksi - Langkah {{ currentStep }}
        </h3>
        <p class="text-sm font-mono whitespace-pre-wrap">
            {{ currentInstruction }}
        </p>
    </div>
</template>