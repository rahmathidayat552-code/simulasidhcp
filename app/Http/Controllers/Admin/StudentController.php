<?php

// PERBAIKI BARIS INI: ganti Http-Controllers menjadi Http\Controllers
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class StudentController extends Controller
{
    /**
     * Menampilkan daftar siswa.
     */
    public function index()
    {
        return Inertia::render('Admin/Students/Index', [
            'students' => Student::latest()->paginate(10),
        ]);
    }

    /**
     * Menyimpan data siswa baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max|:255',
            'nisn' => 'required|string|max:255|unique:students',
            'password' => 'required|string|min:8',
        ]);

        Student::create([
            'name' => $request->name,
            'nisn' => $request->nisn,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.students.index');
    }

    /**
     * Memperbarui data siswa yang ada.
     */
    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nisn' => ['required', 'string', 'max:255', Rule::unique('students')->ignore($student->id)],
            'password' => 'nullable|string|min:8', // Password opsional saat update
        ]);

        $student->update([
            'name' => $request->name,
            'nisn' => $request->nisn,
        ]);

        // Hanya update password jika diisi
        if ($request->filled('password')) {
            $student->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('admin.students.index');
    }

    /**
     * Menghapus data siswa.
     */
    public function destroy(Student $student)
    {
        $student->delete();
        return redirect()->route('admin.students.index');
    }
}