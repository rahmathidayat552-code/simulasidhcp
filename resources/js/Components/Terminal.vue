<script setup>
import { ref, onMounted, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
    sessionId: {
        type: Number,
        required: true,
    },
    initialLogs: {
        type: Array,
        default: () => [],
    },
});

// Mendefinisikan event yang akan dikirim ke parent component
const emit = defineEmits(['command-success', 'open-editor']);

const history = ref([]);
const command = ref('');
const isLoading = ref(false);
const terminalOutput = ref(null); // Ref untuk div output
const terminalInput = ref(null); // Ref untuk elemen input

// Saat komponen pertama kali dimuat
onMounted(() => {
    // Isi history dengan log yang sudah ada dari server
    history.value = props.initialLogs.map(log => ({
        command: log.command,
        output: log.response_output,
    }));
    focusInput(); // Langsung fokus ke input saat halaman dimuat
});

// Fungsi untuk fokus ke elemen input
const focusInput = () => {
    terminalInput.value?.focus();
};

// Fungsi untuk scroll otomatis ke bawah
const scrollToBottom = async () => {
    await nextTick(); // Menunggu DOM update sebelum scroll
    if (terminalOutput.value) {
        terminalOutput.value.scrollTop = terminalOutput.value.scrollHeight;
    }
};

// Fungsi yang dieksekusi saat tombol Enter ditekan
const handleCommand = async () => {
    if (isLoading.value || command.value.trim() === '') return;

    isLoading.value = true;
    const currentCommand = command.value;
    command.value = ''; // Kosongkan input field

    // Tampilkan perintah yang diketik di history agar ada feedback instan
    history.value.push({ command: currentCommand, output: '' });
    await scrollToBottom();

    try {
        // Kirim perintah ke backend menggunakan axios
        const response = await axios.post(route('exam.execute'), {
            session_id: props.sessionId,
            command: currentCommand,
        });

        // Update entri terakhir di history dengan output dari server
        history.value[history.value.length - 1].output = response.data.output;

        // Cek jika output berisi trigger untuk membuka nano editor
        if (response.data.output.includes("Opening nano editor")) {
            emit('open-editor'); // Kirim event 'open-editor' ke parent
        }
        
        // Jika perintahnya benar, kirim event 'command-success'
        if (response.data.is_correct) {
            emit('command-success');
        }

    } catch (error) {
        history.value[history.value.length - 1].output = "Error: Gagal terhubung ke server.";
        console.error("Error executing command:", error);
    } finally {
        isLoading.value = false;
        await scrollToBottom();
        focusInput();
    }
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
                    v-model="command"
                    @keydown.enter.prevent="handleCommand"
                    :disabled="isLoading"
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