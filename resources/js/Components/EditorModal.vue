<script setup>
import { ref, watch } from 'vue';
import PrimaryButton from './PrimaryButton.vue';
import SecondaryButton from './SecondaryButton.vue';

const props = defineProps({
    show: Boolean,
    initialContent: String,
});

const emit = defineEmits(['close', 'save']);

const content = ref('');

// Setiap kali modal ditampilkan, isi textarea dengan konten awal
watch(() => props.show, (newValue) => {
    if (newValue) {
        content.value = props.initialContent || '';
    }
});

const saveAndClose = () => {
    emit('save', content.value);
    emit('close');
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-60 z-40 flex justify-center items-center">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl z-50">
            <h3 class="text-lg font-bold mb-4">Nano Editor Simulation</h3>

            <textarea
                v-model="content"
                class="w-full h-80 font-mono text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Masukkan konfigurasi Anda di sini..."
            ></textarea>

            <div class="mt-4 flex justify-end space-x-2">
                <SecondaryButton @click="$emit('close')">Cancel</SecondaryButton>
                <PrimaryButton @click="saveAndClose">Simpan & Keluar</PrimaryButton>
            </div>
        </div>
    </div>
</template>