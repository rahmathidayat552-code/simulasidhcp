<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import axios from 'axios';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InstructionPanel from '@/Components/InstructionPanel.vue';
import Terminal from '@/Components/Terminal.vue';
import EditorModal from '@/Components/EditorModal.vue';
import SubnetInputPanel from '@/Components/SubnetInputPanel.vue';

const props = defineProps({
    examSession: Object,
});

const currentStep = ref(props.examSession.session_data.current_step);
const showEditor = ref(false);
const activeEditorContent = ref('');

const handleOpenEditor = () => {
    const lastCommand = props.examSession.command_logs?.slice(-1)[0]?.command || '';
    if (lastCommand.includes('dhcpd.conf')) {
        activeEditorContent.value = props.examSession.session_data.dhcpd_config_content || '';
    } else if (lastCommand.includes('isc-dhcp-server')) {
        activeEditorContent.value = props.examSession.session_data.interface_config_content || '';
    } else {
        activeEditorContent.value = '';
    }
    showEditor.value = true;
};

const timeLeft = ref(0);
let timerInterval = null;

const getEndTime = () => {
    const startTime = new Date(props.examSession.start_time).getTime();
    return startTime + props.examSession.duration * 60 * 1000;
};

const updateTimer = () => {
    const now = new Date().getTime();
    const endTime = getEndTime();
    const distance = endTime - now;

    if (distance < 0) {
        timeLeft.value = 0;
        clearInterval(timerInterval);
        finalizeExam(true);
    } else {
        timeLeft.value = distance;
    }
};

const formattedTimeLeft = computed(() => {
    if (props.examSession.status === 'completed') return '00:00';
    const minutes = Math.floor((timeLeft.value % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((timeLeft.value % (1000 * 60)) / 1000);
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

onMounted(() => {
    if (props.examSession.status !== 'completed') {
        updateTimer();
        timerInterval = setInterval(updateTimer, 1000);
    }
});

onUnmounted(() => {
    clearInterval(timerInterval);
});

watch(() => props.examSession.session_data.current_step, (newStep) => {
    currentStep.value = newStep;
});

const saveEditorContent = (content) => {
    axios.post(route('exam.submitConfig'), {
        session_id: props.examSession.id,
        config_content: content,
    }).then(() => {
        alert('Konfigurasi berhasil disimpan!');
        showEditor.value = false;
    }).catch(error => {
        alert('Gagal menyimpan konfigurasi.');
        console.error(error);
    });
};

const finalizeForm = useForm({
    session_id: props.examSession.id,
});

const finalizeExam = (isAuto = false) => {
    if (isAuto || confirm("Apakah Anda yakin ingin menyelesaikan ujian?")) {
        if (finalizeForm.processing) return;
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
                
                <div class="flex items-center space-x-4">
                    <div class="font-mono text-xl p-2 bg-gray-800 text-white rounded-md">
                        Sisa Waktu: {{ formattedTimeLeft }}
                    </div>
                    <PrimaryButton @click="finalizeExam(false)" :disabled="finalizeForm.processing">
                        Finalisasi & Kumpul
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6" style="height: 70vh;">
                    
                    <div class="md:col-span-1 sticky self-start top-[154px]">
                        <InstructionPanel :current-step="currentStep" />
                    </div>

                    <div class="md:col-span-2 h-full">
                        <Terminal
                            v-if="currentStep !== 2"
                            :session-id="examSession.id"
                            :initial-logs="examSession.command_logs"
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
            :initial-content="activeEditorContent"
            @close="showEditor = false"
            @save="saveEditorContent"
        />
    </AuthenticatedLayout>
</template>