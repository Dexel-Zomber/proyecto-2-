<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Course;
use App\Models\Score;
use App\Models\User;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'course_id',
        'teacher_id',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'subject_student', 'subject_id', 'student_id')->withTimestamps();
    }

    public function scores()
    {
        return $this->hasMany(Score::class);
    }
}
