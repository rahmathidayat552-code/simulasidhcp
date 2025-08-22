<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Panggil seeder untuk membuat akun guru
        $this->call(UserSeeder::class);

        // Buat satu contoh siswa untuk testing
        Student::create([
            'name' => 'Siswa Coba',
            'nisn' => '1234567890',
            'password' => Hash::make('password'),
        ]);
    }
}