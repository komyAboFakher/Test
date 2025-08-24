<?php

namespace App\Http\Controllers;

use App\Models\Academic;
use Illuminate\Http\Request;

class academicController extends Controller
{
    public function endOfTheFirstSemester(){
        try{
            $academic=Academic::where('currentAcademic',true)->first();
            if(!$academic){
                return response()->json([
                    'status'=>false,
                    'message'=>'the academic is not found',
                ],404);
            }
            $semester='first';
            $avg=AverageController::Average($semester);
            $date=now()->format('Y:m:d');
            $academic->endOfTheFirstSemester=$date;
            $academic->save();
            return response()->json([
                'status'=>true,
                'message'=>'the first semester has been ended',
                'avg'=>$avg,
                //'req'=>$req,
            ]);
        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
            ],500);
        }
    }

    public function startOfTheSecondSemester(){
        try{
            //getting the academic
            $academic=Academic::where('currentAcademic',true)->first();
            if(!$academic){
                return response()->json([
                    'status'=>false,
                    'message'=>'the academic is not found',
                ],404);
            }
            //getting the date and the semester
            $date=now()->format('Y:m:d');
            $academic->academic_semester='second';
            $academic->startOfTheSecondSemester=$date;
            $academic->save();

            return response()->json([
                'status'=>true,
                'message'=>'the second semester has been started'
            ]);

        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
            ],500);
        }
    }

    public function endOfTheYear(){
        try{
            //getting the academic
            $academic=Academic::where('currentAcademic',true)->first();
            if(!$academic){
                return response()->json([
                    'status'=>false,
                    'message'=>'the academic is not found',
                ],404);
            }
            //getting the date and the semester
            $semester='second';
            $avg=AverageController::Average($semester);
            //now we should transfare the students here
            
            ///////////////////////////////////////////
            $date=now()->format('Y:m:d');
            $academic->academic_semester='first';
            $academic->endOfTheSecondSemester=$date;
            $academic->save();

            return response()->json([
                'status'=>true,
                'message'=>'the second semester has been started',
                'avg'=>$avg
            ]);
        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
            ]);
        }
    } 
}
