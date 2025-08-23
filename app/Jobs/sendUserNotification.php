<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use App\Mail\LoginNotification;
use App\Mail\TeacherWelcomeMail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class sendUserNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    Protected $user;
    Protected $password;
    Protected $email;
    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $email,string $password)
    {
        $this->user=$user;
        $this->email=$email;
        $this->password=$password;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->user->email)->send(
            new TeacherWelcomeMail(
                $this->password,
                $this->email
            ));
    }
}
