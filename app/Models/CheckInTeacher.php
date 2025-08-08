<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckInTeacher extends Model
{
    use HasFactory, SoftDeletes;
    Protected $fillable=[
        'teacher_id',
        'student_id',
        'date',
        'checked',
        'sessions',
    ];

//______________________________________________________________________________

    public function teacher(){
        return $this->belongsTo(Teacher::class);
    }
//______________________________________________________________________________

    public function Student(){
        return $this->belongsTo(Student::class);
    }
//______________________________________________________________________________

    public function absenceStudent(){
        return $this->hasMany(AbsenceStudent::class);
    }
}
