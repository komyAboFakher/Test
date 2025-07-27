<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mark extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'teacher_id',
        'student_id',
        'subject_id',
        'mark',
        'success',
        'semester',
        'type',
    ];

    public function SchoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }
    public function Subject()
    {
        return $this->belongsTo(Subject::class);
    }
    public function Teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
    public function Students()
    {
        return $this->belongsTo(Student::class,'student_id');
    }
}
