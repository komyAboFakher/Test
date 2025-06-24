<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleBrief extends Model
{
    use HasFactory;
    protected $fillable=[
      'class_id',
      'day',
      'semester',
      'year',
    ];
    
     public function Clas(){
        return $this->belongsTo(schoolClass::class);
     }
    
     public function Session(){
        return $this->hasMany(Session::class);
     }
}
