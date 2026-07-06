<?php

namespace App\Models;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'subject_id',
        'title',
        'message',
        'severity',
        'resolved',
        'meta',
    ];

    protected $casts = [
        'resolved' => 'boolean',
        'meta' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
