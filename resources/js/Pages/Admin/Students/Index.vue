<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// Komponen-komponen UI
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

defineProps({
    students: Object,
});

// State untuk mengontrol modal
const isModalOpen = ref(false);
const isEditMode = ref(false);

// Form helper dari Inertia
const form = useForm({
    id: null,
    name: '',
    nisn: '',
    password: '',
});

// Judul modal dinamis
const modalTitle = computed(() => isEditMode.value ? 'Edit Siswa' : 'Tambah Siswa Baru');

// Fungsi untuk membuka modal (mode tambah)
const openAddModal = () => {
    isEditMode.value = false;
    form.reset();
    isModalOpen.value = true;
};

// Fungsi untuk membuka modal (mode edit)
const openEditModal = (student) => {
    isEditMode.value = true;
    form.id = student.id;
    form.name = student.name;
    form.nisn = student.nisn;
    form.password = ''; // Kosongkan password saat edit
    isModalOpen.value = true;
};

// Fungsi untuk menutup modal
const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

// Fungsi untuk menyimpan (baik tambah maupun edit)
const saveStudent = () => {
    if (isEditMode.value) {
        // Jika mode edit, kirim request PUT
        form.put(route('admin.students.update', form.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        // Jika mode tambah, kirim request POST
        form.post(route('admin.students.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

// Fungsi untuk menghapus siswa
const deleteStudent = (student) => {
    if (confirm(`Apakah Anda yakin ingin menghapus siswa "${student.name}"?`)) {
        router.delete(route('admin.students.destroy', student.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Kelola Siswa" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kelola Data Siswa</h2>
                <PrimaryButton @click="openAddModal">Tambah Siswa</PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NISN</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="student in students.data" :key="student.id">
                                <td class="px-6 py-4 whitespace-nowrap">{{ student.name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ student.nisn }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <SecondaryButton @click="openEditModal(student)">Edit</SecondaryButton>
                                    <DangerButton @click="deleteStudent(student)">Hapus</DangerButton>
                                </td>
                            </tr>
                            <tr v-if="students.data.length === 0">
                                <td colspan="3" class="px-6 py-4 text-center text-gray-500">Belum ada data siswa.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="isModalOpen" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">{{ modalTitle }}</h2>
                
                <form @submit.prevent="saveStudent" class="mt-6 space-y-6">
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
                        <TextInput id="password" type="password" v-model="form.password" class="mt-1 block w-full" :placeholder="isEditMode ? 'Kosongkan jika tidak diubah' : ''" :required="!isEditMode" />
                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                        <PrimaryButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Simpan
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>