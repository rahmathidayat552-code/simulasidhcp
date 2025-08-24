<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dasbor admin.
     */
    public function index()
    {
        // Merender komponen Vue di 'resources/js/Pages/Admin/Dashboard.vue'
        return Inertia::render('Admin/Dashboard');
    }
}