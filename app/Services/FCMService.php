<?php

namespace App\Services;

use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FCMService
{
    protected $messaging;
    protected $userRepsitory;

    public function __construct()
    {
        $firebase = (new Factory)->withServiceAccount(base_path(env("FIREBASE_CREDENTIALS")));
        $this->messaging = $firebase->createMessaging();
    }

    public function sendNotification($deviceToken, $title, $body, array $data = [])
    {
        try{
            $notification = Notification::create($title, $body);

            if(is_array($deviceToken)){

                $messages = array_map(function($token) use ($notification, $data){
                    return CloudMessage::new()->toToken($token)->withNotification($notification)->withData($data);
                }, $deviceToken);
                return $this->messaging->sendAll($messages);
            }else{
                $message = CloudMessage::new()->toToken($deviceToken)->withNotification($notification)->withData($data);
                return $this->messaging->send($message);
            }
        }catch(\Exception $e){
            return false;
        }
    }

    public function notifyUsers($title,$body){

        $usersFcmTokens = FcmToken::whereNotNull("fcm_token")->pluck("fcm_token")->toArray();
        $this->sendNotification($usersFcmTokens, $title, $body, []);
    }

    public function singleNotification($userFcmToken,$title,$body){
        if($userFcmToken)  $this->sendNotification($userFcmToken, $title, $body, []);
    }

}