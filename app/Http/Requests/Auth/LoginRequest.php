<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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

    public function authenticate(): void
    {
        $credential = $this->input('credential');
        $password = $this->input('password');
        $remember = $this->boolean('remember');

        // Coba login sebagai Guru/Admin terlebih dahulu
        if (filter_var($credential, FILTER_VALIDATE_EMAIL)) {
            if (Auth::guard('web')->attempt(['email' => $credential, 'password' => $password], $remember)) {
                return; // Berhasil login sebagai guru
            }
        } 
        // Jika gagal atau bukan email, coba login sebagai Siswa
        else {
            if (Auth::guard('student')->attempt(['nisn' => $credential, 'password' => $password], $remember)) {
                return; // Berhasil login sebagai siswa
            }
        }

        // Jika keduanya gagal, lemparkan error
        throw ValidationException::withMessages([
            'credential' => trans('auth.failed'),
        ]);
    }
}