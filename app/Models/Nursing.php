<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nursing extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [

        'student_id',
        'nurse_id',
        'record_date',
        'record-type',
        'description',
        'treatment',
        'notes',
        'follow_up',
        'follow_up_date',
        'severity',
    ];


    public function User()
    {
        return $this->belongsTo(User::class);
    }
}
