<?php

namespace App\Http\Controllers;

use App\Models\FcmToken;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
                $product='zag';
                $fcmService=new FCMService;
                $user = Auth::user();
                $fcmService->notifyUsers("Product has been Added", "the Admin " . $user->name . " has added " . $product);

                return response()->json([
                    'status'=>true,
                    'message'=>'notification has been sent successfully!',
                ]);
            }catch(\throwable $th){
                return response()->json([
                    'status'=>false,
                    'message'=>$th->getMessage(),
                ]);
            }
        }

}
