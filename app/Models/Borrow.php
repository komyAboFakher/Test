<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Borrow extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'book_id',
        'serrial_number',
        'borrow_status',
        /////////////////
        'borrow_date',
        'due_date',
        'returned_date',
        'book_status',
        'notes',
    ];

    public function library()
    {
        return $this->belongsTo(Library::class, 'book_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
