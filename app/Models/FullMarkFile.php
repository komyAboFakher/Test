<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FullMarkFile extends Model
{
    use HasFactory;
    protected $fillable = [
        'teacher_id',
        'class_id',
        'subject_id',
        'file_name',
        'file_path',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}
