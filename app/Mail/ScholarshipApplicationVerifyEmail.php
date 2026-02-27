<?php

namespace App\Mail;

use App\Models\ScholarshipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ScholarshipApplicationVerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ScholarshipApplication $application,
        public string $verifyUrl
    ) {}

    public function build()
    {
        return $this->subject('Verify your scholarship application')
            ->markdown('emails.scholarship.verify');
    }
}
