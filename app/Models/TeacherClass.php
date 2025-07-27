<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherClass extends Model
{
    use HasFactory;
    protected $fillable=[
        'teacher_id',
        'class_id',
        'subject_id',
    ];

    public function Teacher(){
        return $this ->belongsTo(Teacher::class,'teacher_id');
    }
    
    public function SchoolClasses(){
        return $this ->belongsto(SchoolClass::class,'class_id');
    }
   
    public function Subject()
    {
        return $this->belongsTo(Subject::class,'subject_id');
    }
}
