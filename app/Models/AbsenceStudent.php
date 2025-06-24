<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsenceStudent extends Model
{
    use HasFactory;
    protected $table = "absence_student";
    protected $fillable=[
        'student_id',
        'absence_num',
        'warning',
    ];

    public function Student(){
        return $this->belongsTo(Student::class);
    }
}
