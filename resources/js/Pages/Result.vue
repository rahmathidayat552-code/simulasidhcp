<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    examSession: Object,
});

const duration = computed(() => {
    if (!props.examSession.start_time || !props.examSession.end_time) {
        return 'N/A';
    }
    const start = new Date(props.examSession.start_time);
    const end = new Date(props.examSession.end_time);
    const diff = Math.abs(end - start) / 1000;
    const minutes = Math.floor(diff / 60);
    const seconds = Math.round(diff % 60);
    return `${minutes} menit ${seconds} detik`;
});

const isSuccess = computed(() => props.examSession.final_result === 'Active (Running)');
</script>

<template>
    <Head title="Hasil Ujian" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Hasil Ujian</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8 text-center">
                        
                        <div v-if="isSuccess">
                            <h3 class="text-3xl font-bold text-green-600">Selamat, Anda Lulus!</h3>
                            <p class="mt-2 text-gray-600">
                                Konfigurasi DHCP Server Anda terdeteksi 
                                <span class="font-bold text-green-600">Active (Running)</span> 
                                dan berjalan dengan benar.
                            </p>
                        </div>
                        
                        <div v-else>
                            <h3 class="text-3xl font-bold text-red-600">Maaf, Anda Gagal</h3>
                            <p class="mt-2 text-gray-600">
                                Konfigurasi DHCP Server Anda terdeteksi 
                                <span class="font-bold text-red-600">Failed</span>. 
                                Silakan coba lagi.
                            </p>
                        </div>

                        <div class="mt-6 border-t pt-6 text-left inline-block">
                            <dl class="grid grid-cols-2 gap-x-4 gap-y-2">
                                <dt class="font-medium text-gray-500">Nama Siswa</dt>
                                <dd class="text-gray-900">{{ examSession.student.name }}</dd>
                                
                                <dt class="font-medium text-gray-500">Hasil Akhir</dt>
                                <dd 
                                    class="font-semibold" 
                                    :class="{ 'text-green-600': isSuccess, 'text-red-600': !isSuccess }"
                                >
                                    {{ examSession.final_result }}
                                </dd>
                                
                                <dt class="font-medium text-gray-500">Durasi Pengerjaan</dt>
                                <dd class="text-gray-900">{{ duration }}</dd>
                            </dl>
                        </div>

                        <div class="mt-8">
                            <Link :href="route('logout')" method="post" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                KLIK SELESAI & LOGOUT
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>