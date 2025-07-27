<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'class_id',
        'serialNum',
        'schoolGraduatedFrom',
        'photo',
        'Gpa',
        'expelled',
        'justification',
    ];
    //_________________________________________________________________________

    public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    //_________________________________________________________________________

    public function AbsenceStudent()
    {
        return $this->hasOne(AbsenceStudent::class);
    }
    //_________________________________________________________________________

    public function parent()
    {
        return $this->belongsTo(Parent::class);
    }
    //__________________________________________________________________________

    public function Marks()
    {
        return $this->hasMany(Mark::class);
    }
    //__________________________________________________________________________

    public function CheckInTeacher()
    {
        return $this->hasMany(CheckInTeacher::class);
    }
    //__________________________________________________________________________

    public function SchoolClass()
    {
        return $this->belongsTo(SchoolClass::class,'class_id');
    }
}
