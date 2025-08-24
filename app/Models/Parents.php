<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parents extends Model
{
    use HasFactory;
    Protected $fillable=[
        'user_id',
        'job',
    ];
    
    public function student(){
        return $this->hasMany(Student::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
