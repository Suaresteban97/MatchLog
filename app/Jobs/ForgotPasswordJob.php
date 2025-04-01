<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ForgotPasswordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $queue = 'high';

    private $token;
    private $email;

    /**
     * Create a new job instance.
     */
    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    /**
     * Execute the job.
     */
    public function handle(MailController $mailCtrl): void
    {
        $mailCtrl->sendResetPasswordEmail($this->token, $this->email);
    }
}
