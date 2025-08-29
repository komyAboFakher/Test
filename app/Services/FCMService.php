<?php

namespace App\Services;

use App\Models\FcmToken;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FCMService
{
    protected $messaging;

    public function __construct()
    {
        $firebase = (new Factory)->withServiceAccount(storage_path(env("FIREBASE_CREDENTIALS")));
        $this->messaging = $firebase->createMessaging();
    }

    public function sendNotification($deviceToken, $title, $body, array $data = [])
    {
        try {
            $notification = Notification::create($title, $body);

            // This part of your code is perfect and does not need changes.
            if (is_array($deviceToken)) {
                $messages = array_map(function ($token) use ($notification, $data) {
                    return CloudMessage::new()->toToken($token)->withNotification($notification)->withData($data);
                }, $deviceToken);
                return $this->messaging->sendAll($messages);
            } else {
                $message = CloudMessage::new()->toToken($deviceToken)->withNotification($notification)->withData($data);
                return $this->messaging->send($message);
            }
        } catch (\Exception $e) {
            // It's better to re-throw the exception to be handled in the controller
            // but returning false is also an option. For now, we'll keep it.
            return false;
        }
    }

    public function notifyUsers($title, $body)
    {
        $usersFcmTokens = FcmToken::whereNotNull("token")->pluck("token")->toArray();
        
        if (empty($usersFcmTokens)) {
            return null; 
        }
        
        return $this->sendNotification($usersFcmTokens, $title, $body, []);
    }
    
    // Also add return to this function for consistency
    public function singleNotification($userFcmToken, $title, $body)
    {
        if ($userFcmToken) {
            return $this->sendNotification($userFcmToken, $title, $body, []);
        }
        return null;
    }
}