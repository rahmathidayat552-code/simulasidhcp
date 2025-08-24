<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Menampilkan halaman login.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Menangani permintaan autentikasi yang masuk.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        // Jika yang login adalah instance dari model User (guru)
        if ($user instanceof \App\Models\User) {
            return redirect()->route('admin.dashboard');
        }

        // Jika bukan, berarti dia adalah Siswa
        return redirect()->intended(route('dashboard', [], false));
    }

    /**
     * Menghancurkan sesi autentikasi.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $guard = Auth::guard('student')->check() ? 'student' : 'web';
        Auth::guard($guard)->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}