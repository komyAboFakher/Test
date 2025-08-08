<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasFactory;
    Protected $fillable=[
        'class_id',        
        'teacher_id',        
        'schedule_brief_id',        
        'subject_id',        
        'cancelled',        
        'session',        
    ];

        public function Subject(){
        return $this->belongsTo(Subject::class);
    }
        public function Clas(){
        return $this->belongsTo(schoolClass::class);
    }
        public function scheduleBrief(){
        return $this->belongsTo(scheduleBrief::class);
    }
        public function teacher(){
        return $this->belongsTo(Teacher::class);
    }
}
