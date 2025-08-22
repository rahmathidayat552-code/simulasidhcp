<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

defineProps({
    students: Object,
});

const form = useForm({
    name: '',
    nisn: '',
    password: '',
});

const submit = () => {
    form.post(route('admin.students.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Kelola Siswa" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kelola Data Siswa</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">Tambah Siswa Baru</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Pastikan NISN unik dan password mudah diingat oleh siswa.
                            </p>
                        </header>

                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            <div>
                                <InputLabel for="name" value="Nama Lengkap" />
                                <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="nisn" value="NISN" />
                                <TextInput id="nisn" v-model="form.nisn" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.nisn" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="password" value="Password" />
                                <TextInput id="password" type="password" v-model="form.password" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.password" class="mt-2" />
                            </div>
                            <PrimaryButton :disabled="form.processing">Simpan</PrimaryButton>
                        </form>
                    </section>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <h2 class="text-lg font-medium text-gray-900">Daftar Siswa</h2>
                    <table class="min-w-full divide-y divide-gray-200 mt-4">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NISN</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="student in students.data" :key="student.id">
                                <td class="px-6 py-4 whitespace-nowrap">{{ student.name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ student.nisn }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>