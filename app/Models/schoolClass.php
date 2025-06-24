<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class schoolClass extends Model
{
   protected $table = "classes";
   protected $fillable = [
      'className',
      'studentsNum',
   ];
   public function students()
   {
      return $this->hasMany(Student::class, 'class_id');
   }
   /////////////////////////////////////////////////////////
   public function ExamSchedules()
   {
      return $this->hasOne(ExamSchedule::class);
   }
   ////////////////////////////////////////////////////////
   //many to many between the teachers and the classes via pivot (teacher-classes)//
   public function Teachers()
   {
      return $this->belongsToMany(Teacher::class, 'teacher_classes', 'teacher_id', 'class_id');
   }
   ////////////////////////////////////////////////////////
   public function Marks()
   {
      return $this->hasmany(Mark::class);
   }
   ////////////////////////////////////////////////////////
   public function ScheduleBriefs()
   {
      return $this->hasmany(ScheduleBrief::class);
   }
   ////////////////////////////////////////////////////////
   public function Sessions()
   {
      return $this->hasmany(Session::class);
   }
}
