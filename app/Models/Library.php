<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Library extends Model
{
    use HasFactory;
    protected $fillable=[
        'title',
        'author',
        'category',
        'publisher',
        'serial_number',
        'shelf_location',
        'description',
    ];

    public function borrow(){
        return $this->hasMany(Borrow::class,'book_id');
    }
}
