<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $email;
    public $deviceType;
    public $loginTime;
    public $ip;
    public $location;

    public function __construct($email, $deviceType, $loginTime, $ip, $location)
    {
        $this->email = $email;
        $this->deviceType = $deviceType;
        $this->loginTime = $loginTime;
        $this->ip = $ip;
        $this->location = $location;
    }

    public function build()
    {
        return $this->subject('Login Notification - Tutors')
                    ->view('emails.login-notification');
    }
}
