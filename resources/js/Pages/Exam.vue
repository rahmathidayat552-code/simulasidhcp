<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
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

// --- LOGIKA TIMER BARU ---
const timeLeft = ref(0);
let timerInterval = null;

// Hitung waktu akhir berdasarkan waktu mulai dan durasi
const getEndTime = () => {
    const startTime = new Date(props.examSession.start_time).getTime();
    return startTime + props.examSession.duration * 60 * 1000;
};

// Fungsi untuk update timer setiap detik
const updateTimer = () => {
    const now = new Date().getTime();
    const endTime = getEndTime();
    const distance = endTime - now;

    if (distance < 0) {
        timeLeft.value = 0;
        clearInterval(timerInterval);
        finalizeExam(true); // Finalisasi otomatis
    } else {
        timeLeft.value = distance;
    }
};

// Format waktu yang tersisa menjadi Menit:Detik
const formattedTimeLeft = computed(() => {
    const minutes = Math.floor((timeLeft.value % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((timeLeft.value % (1000 * 60)) / 1000);
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

// Jalankan timer saat komponen dimuat
onMounted(() => {
    updateTimer();
    timerInterval = setInterval(updateTimer, 1000);
});

// Hentikan timer saat komponen dihancurkan untuk mencegah memory leak
onUnmounted(() => {
    clearInterval(timerInterval);
});
// --- AKHIR LOGIKA TIMER BARU ---


watch(() => props.examSession.session_data.current_step, (newStep) => {
    currentStep.value = newStep;
});

const finalizeForm = useForm({
    session_id: props.examSession.id,
});
// Fungsi finalisasi sekarang menerima parameter untuk membedakan antara manual dan otomatis
const finalizeExam = (isAuto = false) => {
    if (isAuto || confirm("Apakah Anda yakin ingin menyelesaikan ujian?")) {
        finalizeForm.post(route('exam.finalize'));
    }
};

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

                <div class="flex items-center space-x-4">
                <div class="font-mono text-xl p-2 bg-gray-800 text-white rounded-md">
                    Sisa Waktu: {{ formattedTimeLeft }}
                </div>
                
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