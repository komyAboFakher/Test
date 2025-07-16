<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
   use HasFactory;
   protected $table = "classes";
   protected $fillable = [
      'className',
      'studentsNum',
      'currentStudentNumber',
   ];
   public function students()
   {
      return $this->hasMany(Student::class, 'class_id');
   }
   /////////////////////////////////////////////////////////
   public function ExamSchedule()
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
   public function subject()
   {
      return $this->hasMany(Subject::class);
   }
   ////////////////////////////////////////////////////////
   public function Marks()
   {
      return $this->hasmany(Mark::class);
   }
   ////////////////////////////////////////////////////////
   public function ScheduleBrief()
   {
      return $this->hasmany(ScheduleBrief::class);
   }
   ////////////////////////////////////////////////////////
   public function Session()
   {
      return $this->hasmany(Session::class);
   }
   ////////////////////////////////////////////////////////
   public function TeacherClass()
   {
      return $this->hasmany(TeacherClass::class);
   }
}
