<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium">Selamat Datang, Admin!</h3>
                        <p class="mt-1 text-sm text-gray-600">Pilih menu di bawah untuk mengelola simulasi ujian.</p>

                        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">

                            <Link :href="route('admin.students.index')" class="block p-6 bg-gray-50 hover:bg-indigo-100 rounded-lg border border-gray-200 transition">
                                <h4 class="font-bold text-lg text-gray-800">Data Peserta Ujian</h4>
                                <p class="text-sm text-gray-600 mt-1">Tambah, edit, dan hapus data siswa yang akan mengikuti ujian.</p>
                            </Link>

                            <Link :href="route('admin.exam-results.index')" class="block p-6 bg-gray-50 hover:bg-indigo-100 rounded-lg border border-gray-200 transition">
    <h4 class="font-bold text-lg text-gray-800">Data Hasil Exam</h4>
    <p class="text-sm text-gray-600 mt-1">Lihat riwayat dan hasil ujian dari semua siswa.</p>
</Link>

                            <div class="block p-6 bg-gray-50 rounded-lg border border-gray-200 cursor-not-allowed opacity-50">
                                <h4 class="font-bold text-lg text-gray-800">Data Set Exam</h4>
                                <p class="text-sm text-gray-600 mt-1">Atur parameter ujian seperti durasi default, dll. (Fitur segera hadir)</p>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>