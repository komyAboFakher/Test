<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Library extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'title',
        'author',
        'category',
        'publisher',
        'serrial_number',
        'shelf_location',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function borrow()
    {
        return $this->hasMany(Borrow::class, 'book_id');
    }
}
