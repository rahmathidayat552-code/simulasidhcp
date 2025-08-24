<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    examResults: Object,
});

// Helper untuk format tanggal menjadi: 24/08/25, 12:19
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    const dateOptions = { day: '2-digit', month: '2-digit', year: '2-digit' };
    const timeOptions = { hour: '2-digit', minute: '2-digit', hour12: false };

    const formattedDate = date.toLocaleDateString('id-ID', dateOptions);
    const formattedTime = date.toLocaleTimeString('id-ID', timeOptions);

    return `${formattedDate}, ${formattedTime.replace('.', ':')}`;
};

// Helper untuk format durasi menjadi: X min Y dtk
const formatDuration = (session) => {
    if (!session.start_time || !session.end_time) return 'N/A';
    const start = new Date(session.start_time);
    const end = new Date(session.end_time);
    const diffSeconds = Math.abs(end - start) / 1000;
    
    const minutes = Math.floor(diffSeconds / 60);
    const seconds = Math.round(diffSeconds % 60);
    
    return `${minutes} min ${seconds} dtk`;
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
                <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                    <div class="p-4 sm:p-8">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No.</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NISN</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Result</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durasi Pengerjaan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Mulai</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Berakhir</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(result, index) in examResults.data" :key="result.id">
                                    <td class="px-6 py-4 whitespace-nowrap">{{ examResults.from + index }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ result.student.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ result.student.nisn }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-center">-</td>
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
                                    <td class="px-6 py-4 whitespace-nowrap">{{ formatDuration(result) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(result.start_time) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(result.end_time) }}</td>
                                </tr>
                                <tr v-if="examResults.data.length === 0">
                                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">Belum ada data hasil ujian.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> </AuthenticatedLayout>
</template>