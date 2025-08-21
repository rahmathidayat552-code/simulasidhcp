<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InstructionPanel from '@/Components/InstructionPanel.vue';
import Terminal from '@/Components/Terminal.vue';
import EditorModal from '@/Components/EditorModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    examSession: {
        type: Object,
        required: true,
    },
});

// State reaktif untuk langkah saat ini, diinisialisasi dari data server
const currentStep = ref(props.examSession.session_data.current_step);
// State reaktif untuk mengontrol visibilitas modal editor
const showEditor = ref(false);

// Fungsi yang dijalankan saat Terminal mengirim event 'command-success'
const handleSuccess = () => {
    // Naikkan nomor langkah saat ini
    currentStep.value++;
};

// Fungsi yang dijalankan saat Terminal mengirim event 'open-editor'
const handleOpenEditor = () => {
    // Tampilkan modal editor
    showEditor.value = true;
};

// Fungsi untuk menyimpan konten dari modal editor ke backend
const saveEditorContent = async (content) => {
    try {
        await axios.post(route('exam.submitConfig'), {
            session_id: props.examSession.id,
            config_content: content,
        });
        // Beri tahu siswa bahwa konfigurasi mereka sudah disimpan
        alert('Konfigurasi berhasil disimpan!');
    } catch (error) {
        alert('Gagal menyimpan konfigurasi. Silakan coba lagi.');
        console.error(error);
    }
};

// Menggunakan useForm dari Inertia untuk menangani form finalisasi
const finalizeForm = useForm({
    session_id: props.examSession.id,
});

// Fungsi untuk memfinalisasi ujian
const finalizeExam = () => {
    if (confirm("Apakah Anda yakin ingin menyelesaikan ujian? Anda tidak bisa kembali lagi.")) {
        // Kirim request POST ke backend, Inertia akan menangani redirect ke halaman hasil
        finalizeForm.post(route('exam.finalize'));
    }
};
</script>

<template>
    <Head :title="'Ujian Sesi #' + examSession.id" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Simulasi Ujian DHCP Server
                </h2>
                <PrimaryButton @click="finalizeExam" :disabled="finalizeForm.processing">
                    <span v-if="finalizeForm.processing">Memproses...</span>
                    <span v-else>Finalisasi & Kumpul Jawaban</span>
                </PrimaryButton>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6" style="height: 70vh;">
                    
                    <div class="md:col-span-1">
                        <InstructionPanel :current-step="currentStep" />
                    </div>

                    <div class="md:col-span-2">
                        <Terminal
                            :session-id="examSession.id"
                            :initial-logs="examSession.command_logs"
                            @command-success="handleSuccess"
                            @open-editor="handleOpenEditor"
                        />
                    </div>
                </div>
            </div>
        </div>

        <EditorModal
            :show="showEditor"
            :initial-content="examSession.session_data.dhcp_config"
            @close="showEditor = false"
            @save="saveEditorContent"
        />
    </AuthenticatedLayout>
</template>