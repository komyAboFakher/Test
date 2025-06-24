<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckInEmployee extends Model
{
    use HasFactory;
    protected $fillable=[
        'user_id',
        'attend',
        'leave_type',
    ];

        public function User()
    {
        return $this->belongTo(User::class);
    }

}
