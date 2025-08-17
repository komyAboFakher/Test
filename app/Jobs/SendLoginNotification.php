<?php

namespace App\Jobs;

use App\Mail\LoginNotification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class SendLoginNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $deviceDetails;
    protected $loginTime;
    protected $ip;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, string $deviceDetails, string $loginTime, string $ip)
    {
        $this->user = $user;
        $this->deviceDetails = $deviceDetails;
        $this->loginTime = $loginTime;
        $this->ip = $ip;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 1. Get location from IP address
        $response = Http::get("https://ipinfo.io/{$this->ip}/json");
        $location = 'Unknown Location';
        if ($response->successful()) {
            $data = $response->json();
            $city = $data['city'] ?? null;
            $region = $data['region'] ?? null;
            $country = $data['country'] ?? null;
            if ($city && $region && $country) {
                $location = "$city, $region, $country";
            }
        }

        // 2. Send the email
        // Note: You should have an App\Mail\LoginNotification Mailable class for this
        Mail::to($this->user->email)->send(
            new LoginNotification(
                $this->user->email,
                $this->deviceDetails,
                $this->loginTime,
                $this->ip,
                $location
            )
        );
    }
}