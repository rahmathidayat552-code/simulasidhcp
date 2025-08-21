<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommandLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'exam_session_id',
        'step_number',
        'command',
        'is_correct',
        'response_output',
    ];

    /**
     * Get the exam session that the command log belongs to.
     */
    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }
}