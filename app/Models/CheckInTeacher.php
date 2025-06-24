<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckInTeacher extends Model
{
    use HasFactory;
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
        return $this->hasMany(Student::class);
    }
//______________________________________________________________________________

    public function absenceStudent(){
        return $this->hasMany(AbsenceStudent::class);
    }
}
