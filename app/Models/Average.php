<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Average extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'average_1',
        'average_2',
        'average_final',
        'academic_year'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
