<script setup>
import { ref, onMounted, nextTick, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    sessionId: Number,
    initialLogs: Array,
});

const emit = defineEmits(['open-editor']);

const form = useForm({
    session_id: props.sessionId,
    command: '',
});

const history = ref([]);
const terminalOutput = ref(null);
const terminalInput = ref(null);

// Fungsi ini sekarang juga akan menyimpan status 'is_correct'
const updateHistory = (logs) => {
    history.value = logs.map(log => ({
        command: log.command,
        output: log.response_output,
        is_correct: log.is_correct, // <-- Tambahkan ini
    }));
};

watch(() => props.initialLogs, (newLogs) => {
    updateHistory(newLogs);
    scrollToBottom();
}, { deep: true });

onMounted(() => {
    updateHistory(props.initialLogs);
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

    const commandToExecute = form.command;
    // Tambahkan entri sementara ke history (is_correct default true)
    history.value.push({ command: commandToExecute, output: '', is_correct: true });
    
    form.command = '';

    form.transform(() => ({
        session_id: props.sessionId,
        command: commandToExecute,
    })).post(route('exam.execute'), {
        preserveScroll: true,
        onSuccess: (page) => {
            const lastLog = page.props.examSession.command_logs.slice(-1)[0];
            if (lastLog && lastLog.response_output.includes("Opening nano editor")) {
                emit('open-editor');
            }
        },
        onFinish: () => {
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
                <pre
                    v-if="item.output"
                    class="whitespace-pre-wrap"
                    :class="{ 'text-red-500': !item.is_correct, 'text-gray-300': item.is_correct }"
                >{{ item.output }}</pre>
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