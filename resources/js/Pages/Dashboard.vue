<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

// Menggunakan useForm dari Inertia untuk menangani request
const form = useForm({});

// Fungsi yang akan dipanggil saat tombol di-klik
const startExam = () => {
    // Mengirim request POST ke route 'exam.start'
    // Jika berhasil, backend akan me-redirect ke halaman ujian
    form.post(route('exam.start'), {
        onError: (errors) => {
            // Tangani jika ada error (misalnya, tampilkan notifikasi)
            alert('Gagal memulai ujian. Silakan coba lagi.');
            console.error(errors);
        },
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900">Selamat Datang!</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Anda siap untuk memulai ujian praktik konfigurasi DHCP Server.
                            Pastikan Anda sudah memahami materi. Klik tombol di bawah ini untuk memulai.
                        </p>
                        
                        <div class="mt-6">
                            <PrimaryButton @click="startExam" :disabled="form.processing">
                                <span v-if="form.processing">Memproses...</span>
                                <span v-else>Mulai Ujian Praktik DHCP Server</span>
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>