<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;
    Protected $fillable =[
        'role',
        'name',
        'middle_name',
        'last_name',
        'phone_number',
        'email',
        'cv',
        'motivation_letter',
        'qualification',
        'subject',
    ];

 
    public function user(){
        return $this->belongsTo(User::class); 
    }
}
