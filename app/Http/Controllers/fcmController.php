<?php

namespace App\Http\Controllers;

use App\Models\FcmToken;
use Illuminate\Http\Request;

class fcmController extends Controller
{
        public static function saveFcmToken(string $token)
        {
            // $request->validate([
            //     'token' => 'required|string',
            // ]);

            $user = auth()->user(); // if using Sanctum or Passport

            // Save or update token
            FcmToken::updateOrCreate(
                ['user_id' => $user->id],
                ['token' => $token]
            );

            return response()->json([
                'status' => true,
                'message' => 'Token saved successfully',
            ]);
        }

}
