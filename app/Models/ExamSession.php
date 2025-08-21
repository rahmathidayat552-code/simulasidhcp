<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'status',
        'final_result',
        'start_time',
        'end_time',
        'session_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'session_data' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the student that owns the exam session.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the command logs for the exam session.
     */
    public function commandLogs()
    {
        return $this->hasMany(CommandLog::class);
    }
}