<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacation extends Model
{
    use HasFactory;
    protected $fillable=[
        'user_id',
        'vacation_start',
        'vacation_end',
        'vacation_description',
    ];
        public function User()
    {
        return $this->belongsTo(User::class);
    }
}
