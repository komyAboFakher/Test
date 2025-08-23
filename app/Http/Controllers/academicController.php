<?php

namespace App\Http\Controllers;

use App\Models\Academic;
use Illuminate\Http\Request;

class academicController extends Controller
{
    public function endOfTheSemester(){
        try{
            $academic=Academic::where('currentAcademic',true)->first();
            if(!$academic){
                return response()->json([
                    'status'=>false,
                    'message'=>'the academic is not found',
                ],404);
            }
            $academic->academic_semester='second';
            $academic->save();
            $semester='first';
            $avg=AverageController::Average($semester);
            return response()->json([
                'status'=>true,
                'message'=>'the semester has been upadated',
                'avg'=>$avg,
                //'req'=>$req,
            ]);
        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
            ]);
        }
    }
}
