<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'reactables');
    }

    
}
