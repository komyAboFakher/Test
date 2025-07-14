<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportedComment extends Model
{
    use HasFactory;
    protected $fillable = [

        'reporter_id',
        'comment_id',
        'reason'

    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    

    public function Comment()
    {
        return $this->belongsTo(Comment::class, 'comment_id')->with('user','parent','replies');
    }
}
