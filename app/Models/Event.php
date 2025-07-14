<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'event_name',
        'post',
        'is_published',
        'photos'
    ];



    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function Comment()
    {
        return $this->hasMany(Comment::class, 'event_id');
    }

    public function media()
    {
        return $this->hasMany(Media::class, 'event_id');
    }

    public function reactions()
    {
        return $this->morphToMany(Reaction::class, 'reactable','reactables')
            ->withPivot('user_id')
            ->withTimestamps();
    }
}
