<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsenceTeacher extends Model
{
    use HasFactory;
    Protected $fillable=[
            'teacher_id',
            'abcense_work_paid',
            'abcense_work_unpaid',
            'vacations',
            'medical',
            'personal',
            'motherhood_leave'
    ];


    
    public function Teacher(){
        return $this ->belongsTo(Teacher::class);
    }
    
}
