<?php

namespace App\Http\Controllers;

use App\Models\FcmToken;
use Illuminate\Http\Request;

class fcmController extends Controller
{
        public static function saveFcmToken(string $token,int $userId)
        {
            // $request->validate([
            //     'token' => 'required|string',
            // ]);

            $user = auth()->user(); // if using Sanctum or Passport

            // Save or update token
            FcmToken::updateOrCreate([
                'user_id' => $userId,
                'token' => $token
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Token saved successfully',
            ]);
        }

        public function testFcmoken(){
            try{

            }catch(\throwable $th){
                return response()->json([
                    'status'=>false,
                    'message'=>$th->getMessage(),
                ]);
            }
        }

}
