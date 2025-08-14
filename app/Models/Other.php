<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Other extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'certification',
        'photo',
        'salary'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
