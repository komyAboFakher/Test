<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'certification',
        'photo',
        'subject',
        'salary',
    ];

    //______________________________________________________________________________________

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    //_______________________________________________________________________________________

    public function Mark()
    {
        return $this->hasMany(Mark::class);
    }
    //_______________________________________________________________________________________

    public function Session()
    {
        return $this->hasMany(Session::class);
    }
    //_______________________________________________________________________________________
    public function FullMarkFile()
    {
        return $this->hasMany(FullMarkFile::class, 'teacher_id');
    }
    //_______________________________________________________________________________________

    public function AbsenceTeacher()
    {
        return $this->hasOne(AbsenceTeacher::class);
    }
    //_______________________________________________________________________________________

    public function checkInTeacher()
    {
        return $this->hasMany(checkInTeacher::class);
    }

    //_______________________________________________________________________________________

    public function SchoolClasses()
    {
        return $this->belongsToMany(SchoolClass::class, 'teacher_classes', 'teacher_id', 'class_id');
    }
    //_______________________________________________________________________________________

    public function Subject()
    {
        return $this->belongsToMany(Subject::class, 'teacher_classes', 'teacher_id', 'subject_id');
    }
}
