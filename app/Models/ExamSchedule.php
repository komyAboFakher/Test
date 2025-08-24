<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSchedule extends Model
{
    use HasFactory;
    Protected $table="exam_schedule";
    protected $fillable=[
        'schedule_pdf',
        'semester',
        'grade',
        'type',
    ];
    public function Clas(){
        return $this->belongsTo(SchoolClass::class);
    }
}
