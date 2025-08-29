<?php

namespace App\Jobs;

use App\Models\FcmToken;
use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSkipperNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fcmTokens;
    protected $title;
    protected $body;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $fcmTokens, string $title, string $body)
    {
        $this->fcmTokens = $fcmTokens;
        $this->title = $title;
        $this->body = $body;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // This is the logic moved from your controller
        if (empty($this->fcmTokens)) {
            return; // Nothing to do
        }

        $fcmService = new FCMService();
        $report = $fcmService->sendNotification($this->fcmTokens, $this->title, $this->body);

        if ($report) {
            $successCount = $report->successes()->count();
            $failureCount = $report->failures()->count();
            Log::info("FCM Dispatch Report: {$successCount} sent successfully, {$failureCount} failed.");

            // Clean up invalid tokens
            $invalidTokens = $report->invalidTokens();
            if (!empty($invalidTokens)) {
                FcmToken::whereIn('token', $invalidTokens)->delete();
            }

            // Log detailed errors
            if ($report->hasFailures()) {
                foreach ($report->failures()->getItems() as $failure) {
                    Log::error("FCM failed for token [{$failure->target()->value()}]: {$failure->error()->getMessage()}");
                }
            }
        } else {
            Log::error("FCM service failed to return a report for job.");
        }
    }
}