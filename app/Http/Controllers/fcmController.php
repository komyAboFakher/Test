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


public function testFcmoken()
{
    try {
        $product = 'zag';
        $fcmService = new FCMService;
        //$user = Auth::user();
        $userName='3aw';


        $report = $fcmService->notifyUsers(
            "Product has been Added",
            "the Admin " . $userName . " has added " . $product
        );

        if (!$report) {
            return response()->json([
                'status' => true,
                'message' => 'Action completed, but no notifications were sent (no tokens found).',
            ]);
        }

        $invalidTokens = $report->invalidTokens();
        if (!empty($invalidTokens)) {
            FcmToken::whereIn('token', $invalidTokens)->delete();
        }

        // ✨ DEBUGGING CODE ADDED HERE ✨
        // Get the detailed error messages for any failures.
        $failureDetails = [];
        foreach ($report->failures()->getItems() as $failure) {
            $failureDetails[] = [
                'target_token' => $failure->target()->value(),
                'error_message' => $failure->error()->getMessage(),
                //'error_details' => $failure->error()->getDetails(),
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Notification dispatch completed.',
            'details' => [
                'successfully_sent' => $report->successes()->count(),
                'failed_to_send' => $report->failures()->count(),
                'invalid_tokens_removed' => count($invalidTokens),
                'failures' => $failureDetails, // Add the detailed errors to the response
            ]
        ]);

    } catch (\Throwable $th) {
        return response()->json([
            'status' => false,
            'message' => 'An unexpected error occurred.',
            'error' => $th->getMessage(),
        ], 500);
    }
}
}