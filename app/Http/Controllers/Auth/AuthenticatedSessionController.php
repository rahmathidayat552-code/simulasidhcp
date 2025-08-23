<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia; // <-- Pastikan Inertia di-import
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response // <-- Ubah return type menjadi Response
    {
        // UBAH BAGIAN INI: Ganti view() dengan Inertia::render()
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
       $request->authenticate();

        $request->session()->regenerate();

        // --- TAMBAHKAN LOGIKA REDIRECT INI ---
        if (Auth::guard('student')->check()) {
            return redirect()->intended(route('dashboard', [], false));
        }

        // Jika bukan student, berarti admin/guru
        return redirect()->route('admin.students.index');

        // Jika yang login adalah instance dari model User (guru)
        if ($user instanceof \App\Models\User) {
            return redirect()->route('admin.students.index');
        }
        return redirect()->intended(route('dashboard', [], false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Logout dari guard yang sedang aktif
        $guard = Auth::guard('student')->check() ? 'student' : 'web';
        Auth::guard($guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}