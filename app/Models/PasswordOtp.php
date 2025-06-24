<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordOtp extends Model
{
    protected $fillable=[
        'user_id',
        'otp',
        'expires_at',//set the epiry time
        'verified',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified' => 'boolean',
    ];

        public function user()
    {
        return $this->belongsTo(User::class);
    } 
}
