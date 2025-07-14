<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;
    protected $fillable = [
        'event_id',
        'user_id',
        'parent_id',
        'content',
    ];

    public function Event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    // defining the selfe referential relation between the comment and the reply, nested comments

    // here for "who i am replying for?"
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }
    // here for "who replied to me?"
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    /*example for more understanding MAJD :
        
    Alice posts: "What's everyone doing this weekend?" (Comment ID 1, no parent)

    Bob replies: "Going hiking!" (Comment ID 2, parent_id = 1)

    Charlie replies to Bob: "Which trail?" (Comment ID 3, parent_id = 2)

        */
    //______________________________________________________________________________

    public function reportedComment()
    {
        return $this->hasMany(ReportedComment::class, 'comment_id');
    }


    public function reactions()
    {
        return $this->morphToMany(Reaction::class, 'reactables')
            ->withPivot('user_id')
            ->withTimestamps();
    }

    ////////////////////////////////////////////////////////////////////////////////////////////


    public function ancestors()
    {
        return $this->parent()->with(['ancestors.user']);
    }


    public function descendants()
    {
        return $this->replies()->with(['descendants.user']);
    }

    public function allDescendants()
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->with('allDescendants');
    }

    public function allAncestors()
    {
        return $this->belongsTo(Comment::class, 'parent_id')
            ->with('allAncestors');
    }
}
