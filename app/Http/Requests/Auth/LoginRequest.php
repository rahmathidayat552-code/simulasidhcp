<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;   // <-- Tambahkan ini
use App\Models\Student; // <-- Tambahkan ini

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'credential' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $credential = $this->input('credential');
        $password = $this->input('password');
        $remember = $this->boolean('remember');

        $user = null;

        // Cek apakah input adalah email
        if (filter_var($credential, FILTER_VALIDATE_EMAIL)) {
            // Jika ya, cari di tabel 'users' (untuk Guru/Admin)
            $user = User::where('email', $credential)->first();
        } else {
            // Jika bukan, cari di tabel 'students' (untuk Siswa)
            $user = Student::where('nisn', $credential)->first();
        }

        // Jika user tidak ditemukan ATAU password salah
        if (!$user || !Hash::check($password, $user->password)) {
            // Lemparkan error 'auth.failed'
            throw ValidationException::withMessages([
                'credential' => trans('auth.failed'),
            ]);
        }

        // Jika user ditemukan dan password cocok, loginkan
        Auth::login($user, $remember);
    }
}