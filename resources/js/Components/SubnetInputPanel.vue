<script setup>
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    sessionId: {
        type: Number,
        required: true,
    },
});

const form = useForm({
    session_id: props.sessionId,
    subnet_cidr: '192.168.1.0/24', // Nilai default
});

const submitSubnet = () => {
    form.post(route('exam.submitSubnet'), {
        preserveScroll: true, // Mencegah scroll ke atas setelah validasi
        onSuccess: () => {
            // Backend akan handle redirect, tidak perlu aksi khusus di sini
        },
        onError: () => {
            // Pesan error akan otomatis ditampilkan oleh <InputError>
        }
    });
};
</script>

<template>
    <div class="bg-gray-100 p-4 rounded-md border">
        <form @submit.prevent="submitSubnet">
            <div>
                <InputLabel for="subnet_cidr" value="Masukkan Jaringan (CIDR)" />
                <TextInput
                    id="subnet_cidr"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.subnet_cidr"
                    required
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.subnet_cidr" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Simpan & Lanjutkan
                </PrimaryButton>
            </div>
        </form>
    </div>
</template>