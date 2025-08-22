<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import axios from 'axios';

// Layout & Komponen Bawaan
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

// Komponen Kustom untuk Halaman Ujian
import InstructionPanel from '@/Components/InstructionPanel.vue';
import Terminal from '@/Components/Terminal.vue';
import EditorModal from '@/Components/EditorModal.vue';
import SubnetInputPanel from '@/Components/SubnetInputPanel.vue';

// Menerima data sesi ujian dari controller sebagai props
const props = defineProps({
    examSession: {
        type: Object,
        required: true,
    },
});

// Membuat state reaktif untuk langkah saat ini agar UI bisa diperbarui
// Nilai awal diambil dari data yang dikirim server
const currentStep = ref(props.examSession.session_data.current_step);

// State reaktif untuk mengontrol kapan modal editor ditampilkan
const showEditor = ref(false);

// Setiap kali Inertia memuat ulang data (setelah submit form/perintah),
// pastikan `currentStep` di frontend sinkron dengan data terbaru dari backend.
watch(() => props.examSession.session_data.current_step, (newStep) => {
    currentStep.value = newStep;
});


// --- HANDLER UNTUK EVENT DARI KOMPONEN ANAK ---

// Fungsi ini dijalankan saat komponen Terminal berhasil mengeksekusi perintah
// dan mengirim event 'command-success'


// Fungsi ini dijalankan saat Terminal mendeteksi output untuk membuka nano
// dan mengirim event 'open-editor'
const handleOpenEditor = () => {
    // Tampilkan modal editor
    showEditor.value = true;
};


// --- FUNGSI UNTUK BERINTERAKSI DENGAN BACKEND ---

// Fungsi untuk menyimpan konten dari modal editor
const saveEditorContent = async (content) => {
    try {
        await axios.post(route('exam.submitConfig'), {
            session_id: props.examSession.id,
            config_content: content,
        });
        // Beri notifikasi kepada siswa
        alert('Konfigurasi berhasil disimpan!');
    } catch (error) {
        alert('Gagal menyimpan konfigurasi. Silakan coba lagi.');
        console.error("Error saving config:", error);
    }
};

// Menyiapkan form untuk finalisasi ujian menggunakan helper dari Inertia
const finalizeForm = useForm({
    session_id: props.examSession.id,
});

// Fungsi yang dipanggil saat tombol finalisasi diklik
const finalizeExam = () => {
    // Minta konfirmasi dari siswa sebelum mengirim
    if (confirm("Apakah Anda yakin ingin menyelesaikan dan mengumpulkan ujian? Tindakan ini tidak dapat dibatalkan.")) {
        // Kirim request POST. Inertia akan otomatis menangani redirect
        // ke halaman hasil jika backend mengirimkan redirect response.
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
                    Simulasi Ujian Praktik DHCP Server
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
                            v-if="currentStep !== 2"
                            :session-id="examSession.id"
                            :initial-logs="examSession.command_logs"
                            @command-success="handleSuccess"
                            @open-editor="handleOpenEditor"
                        />
                        <SubnetInputPanel
                            v-else
                            :session-id="examSession.id"
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