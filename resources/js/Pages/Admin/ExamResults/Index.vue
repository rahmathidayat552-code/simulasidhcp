<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    examResults: Object,
});

// Helper untuk format tanggal
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('id-ID', {
        year: 'numeric', month: 'long', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

// Helper untuk menghitung durasi kerja
const calculateDuration = (session) => {
    if (!session.start_time || !session.end_time) return 'N/A';
    const start = new Date(session.start_time);
    const end = new Date(session.end_time);
    const diff = Math.abs(end - start) / 1000;
    const minutes = Math.floor(diff / 60);
    const seconds = Math.round(diff % 60);
    return `${minutes} menit ${seconds} dtk`;
};
</script>

<template>
    <Head title="Hasil Ujian" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Data Hasil Ujian Siswa</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NISN</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu Mulai</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu Berakhir</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durasi Kerja</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Result</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="(result, index) in examResults.data" :key="result.id">
                                <td class="px-6 py-4 whitespace-nowrap">{{ examResults.from + index }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ result.student.nisn }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(result.start_time) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(result.end_time) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ calculateDuration(result) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-800': result.final_result === 'Active (Running)',
                                            'bg-red-100 text-red-800': result.final_result === 'Failed',
                                        }"
                                    >
                                        {{ result.final_result }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold">
                                    -
                                </td>
                            </tr>
                            <tr v-if="examResults.data.length === 0">
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">Belum ada data hasil ujian.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>