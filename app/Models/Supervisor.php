<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    use HasFactory;
    protected $fillable=[
        'user_id',
        'certification',
        'photo',
        'salary',
    ];
//__________________________________________________________________
    public function user(){
        return $this->belongsTo(User::class);
    }

//__________________________________________________________________
    public function event(){
        return $this->hasMany(Event::class);
    }
//__________________________________________________________________
    public function examSchedule(){
        return $this->hasMany(ExamSchedule::class);
    }


}
