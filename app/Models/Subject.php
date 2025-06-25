<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'subjectName',
        'min_mark',
        'max_mark',
        'grade',
    ];

    public function Teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_classes', 'subject_id', 'teacher_id');
    }


    public function SchoolClass(){
        return $this->belongsTo(SchoolClass::class);
    }

    public function Session()
    {
        return $this->hasMany(Session::class);
    }

    public function Mark()
    {
        return $this->hasMany(Mark::class);
    }
}
