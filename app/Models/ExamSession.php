<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'status',
        'duration',
        'final_result',
        'start_time',
        'end_time',
        'session_data',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'session_data' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Mendefinisikan relasi "milik" ke model Student.
     * Setiap sesi ujian dimiliki oleh satu siswa.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Mendefinisikan relasi "memiliki banyak" ke model CommandLog.
     * Setiap sesi ujian memiliki banyak log perintah.
     */
    public function commandLogs(): HasMany
    {
        return $this->hasMany(CommandLog::class);
    }
}