<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    sessionId: Number,
    initialLogs: Array,
});

const emit = defineEmits(['open-editor']);

// Menyiapkan form helper Inertia untuk menangani pengiriman perintah
const form = useForm({
    session_id: props.sessionId,
    command: '',
});

const history = ref([]);
const terminalOutput = ref(null);
const terminalInput = ref(null);

onMounted(() => {
    history.value = props.initialLogs.map(log => ({
        command: log.command,
        output: log.response_output,
    }));
    focusInput();
});

const focusInput = () => {
    terminalInput.value?.focus();
};

const scrollToBottom = async () => {
    await nextTick();
    if (terminalOutput.value) {
        terminalOutput.value.scrollTop = terminalOutput.value.scrollHeight;
    }
};

const handleCommand = () => {
    if (form.processing || form.command.trim() === '') return;

    const currentCommand = form.command;

    history.value.push({ command: currentCommand, output: '' });
    scrollToBottom();

    // Menggunakan form.post dari Inertia
    form.post(route('exam.execute'), {
        preserveScroll: true,
        onSuccess: (page) => {
            // Update history dengan data terbaru dari server
            const newLogs = page.props.examSession.command_logs;
            const lastLog = newLogs[newLogs.length - 1];
            
            if (lastLog) {
                history.value[history.value.length - 1].output = lastLog.response_output;

                if (lastLog.response_output.includes("Opening nano editor")) {
                    emit('open-editor');
                }
            }
        },
        onError: (errors) => {
            history.value[history.value.length - 1].output = errors.command || "Error: Gagal mengeksekusi perintah.";
        },
        onFinish: () => {
            // --- INI ADALAH BAGIAN PENTING ---
            // Mengosongkan kolom input setelah request selesai.
            form.reset('command');
            
            scrollToBottom();
            focusInput();
        },
    });
};
</script>

<template>
    <div 
        class="bg-black text-white font-mono text-sm rounded-lg h-full flex flex-col"
        @click="focusInput"
    >
        <div ref="terminalOutput" class="p-4 overflow-y-auto flex-grow">
            <div v-for="(item, index) in history" :key="index" class="mb-2">
                <div>
                    <span class="text-green-400">student@dhcp-server:~$</span> {{ item.command }}
                </div>
                <pre v-if="item.output" class="text-gray-300 whitespace-pre-wrap">{{ item.output }}</pre>
            </div>
        </div>

        <div class="p-4 border-t border-gray-700">
            <div class="flex items-center">
                <span class="text-green-400 mr-2">student@dhcp-server:~$</span>
                <input
                    ref="terminalInput"
                    v-model="form.command"
                    @keydown.enter.prevent="handleCommand"
                    :disabled="form.processing"
                    type="text"
                    class="bg-transparent border-none text-white w-full focus:ring-0 p-0"
                    placeholder="Ketik perintah di sini dan tekan Enter..."
                    autocomplete="off"
                    autofocus
                />
            </div>
        </div>
    </div>
</template>